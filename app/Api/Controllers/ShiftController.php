<?php
declare(strict_types=1);

namespace Mokko\Api\Controllers;

use Mokko\Api\CrmSupport;

/**
 * Смены и продажи.
 */
final class ShiftController extends ApiController
{
    public function startShift(): void
    {
        $pdo = $this->pdo();
        $uid = $this->uid();
        $p = $this->input();

        $place = (int)(isset($p['place']) ? $p['place'] : 0);
        if (!CrmSupport::placeOwned($pdo, $uid, $place)) {
            \mokko_json(array('error' => 'invalid place'));
        }

        CrmSupport::shiftsReady($pdo);
        $who = CrmSupport::shiftOwnerSql($this->ctx->staffId);

        $stmt = $pdo->prepare('SELECT MAX(id) FROM shifts WHERE uid = ? AND place_id = ? AND ' . $who . ' AND closed_at IS NOT NULL');
        $stmt->execute(array($uid, $place));
        $lastClosed = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT id, created_at, NOW() AS now_ts FROM shifts WHERE uid = ? AND place_id = ? AND ' . $who . ' AND closed_at IS NULL AND id > ? ORDER BY id DESC LIMIT 1');
        $stmt->execute(array($uid, $place, $lastClosed));
        $open = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($open) {
            $elapsed = strtotime((string)$open['now_ts']) - strtotime((string)$open['created_at']);
            if ($elapsed >= 0 && $elapsed < 20 * 3600) {
                \mokko_json(array('id' => (int)$open['id'], 'elapsed' => (int)$elapsed, 'resumed' => true));
            }
        }

        $stmt = $pdo->prepare('INSERT INTO shifts (uid, place_id, staff_id) VALUES (?, ?, ?)');
        $stmt->execute(array($uid, $place, $this->ctx->staffId > 0 ? $this->ctx->staffId : null));

        \mokko_json(array('id' => (int)$pdo->lastInsertId(), 'elapsed' => 0, 'resumed' => false));
    }

    public function shift(): void
    {
        $pdo = $this->pdo();
        $uid = $this->uid();
        $p = $this->input();

        $shiftId = (int)(isset($p['shift']) ? $p['shift'] : 0);
        $place = (int)(isset($p['place']) ? $p['place'] : 0);

        CrmSupport::shiftsReady($pdo);
        if ($shiftId <= 0 && $place > 0) {
            $stmt = $pdo->prepare('SELECT id FROM shifts WHERE uid = ? AND place_id = ? AND ' . CrmSupport::shiftOwnerSql($this->ctx->staffId) . ' AND closed_at IS NULL ORDER BY id DESC LIMIT 1');
            $stmt->execute(array($uid, $place));
            $shiftId = (int)$stmt->fetchColumn();
        }

        $hours = min(24, max(0, (int)(isset($p['hours']) ? $p['hours'] : 0)));
        $min = min(59, max(0, (int)(isset($p['min']) ? $p['min'] : 0)));
        $sec = min(59, max(0, (int)(isset($p['sec']) ? $p['sec'] : 0)));

        $pdo->prepare('UPDATE shifts SET hours = ?, min = ?, sec = ?, closed_at = NOW() WHERE id = ? AND uid = ? AND ' . CrmSupport::shiftOwnerSql($this->ctx->staffId))
            ->execute(array($hours, $min, $sec, $shiftId, $uid));

        $stmt = $pdo->prepare('SELECT pay_method, SUM(total) AS price FROM checks WHERE shift_id = ? AND uid = ? GROUP BY pay_method');
        $stmt->execute(array($shiftId, $uid));
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $result = array();
        foreach ($rows as $row) {
            $result[$row['pay_method']] = $row;
        }
        if (empty($result)) {
            $result = array(
                1 => array('pay_method' => 1, 'price' => 0),
                2 => array('pay_method' => 2, 'price' => 0),
            );
        }

        \mokko_json($result);
    }

    public function buy(): void
    {
        $pdo = $this->pdo();
        $uid = $this->uid();
        $p = $this->input();

        if ($this->action() !== 'check') {
            \mokko_json(array('error' => 'unknown action'));
        }

        $place = (int)(isset($p['place']) ? $p['place'] : 0);
        $shiftId = (int)(isset($p['shift']) ? $p['shift'] : 0);
        $payed = isset($p['payed']) && is_array($p['payed']) ? $p['payed'] : array();

        if (!CrmSupport::placeOwned($pdo, $uid, $place)) {
            \mokko_json(array('error' => 'invalid place'));
        }

        $payMethod = 1;
        if (!empty($payed) && isset($payed[0][3])) {
            $payMethod = (int)$payed[0][3];
        }
        if ($payMethod !== 1 && $payMethod !== 2) {
            $payMethod = 1;
        }

        $itemIds = array();
        foreach ($payed as $item) {
            if (is_array($item) && isset($item[0])) {
                $itemId = (int)$item[0];
                if ($itemId > 0) {
                    $itemIds[$itemId] = true;
                }
            }
        }

        $prices = array();
        if (!empty($itemIds)) {
            $ids = implode(',', array_map('intval', array_keys($itemIds)));
            $stmt = $pdo->prepare("SELECT id, price FROM items WHERE uid = ? AND id IN ($ids)");
            $stmt->execute(array($uid));
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $prices[(int)$row['id']] = (float)$row['price'];
            }
        }

        $calc = 0.0;
        foreach ($payed as $item) {
            if (!is_array($item) || !isset($item[0])) {
                continue;
            }
            $itemId = (int)$item[0];
            $qty = isset($item[1]) ? (int)$item[1] : 0;
            if ($qty <= 0) {
                continue;
            }
            if (isset($prices[$itemId])) {
                $calc += $prices[$itemId] * $qty;
            } else {
                $calc += isset($item[4]) ? (float)$item[4] : 0;
            }
        }
        $total = round($calc, 2);

        $stmt = $pdo->prepare('INSERT INTO checks (uid, place_id, shift_id, items, total, pay_method) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute(array(
            $uid, $place, $shiftId,
            json_encode($payed, JSON_UNESCAPED_UNICODE),
            $total, $payMethod,
        ));
        $checkId = (int)$pdo->lastInsertId();

        if (!empty($payed[0][2])) {
            $guestId = (int)$payed[0][2];
            $pdo->prepare('UPDATE guests SET status = "paid" WHERE id = ? AND uid = ?')->execute(array($guestId, $uid));
        }

        \mokko_json(array('Id' => $checkId));
    }
}
