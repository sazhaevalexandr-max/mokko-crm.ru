<?php
declare(strict_types=1);

namespace Mokko\Api\Controllers;

use Mokko\Api\CrmSupport;

/**
 * Статистика: выручка, продажи и смены.
 */
final class StatController extends ApiController
{
    public function stat(): void
    {
        $pdo = $this->pdo();
        $uid = $this->uid();
        $p = $this->input();

        $place = (int)(isset($p['place']) ? $p['place'] : 0);
        $startDate = CrmSupport::date(isset($p['start_date']) ? $p['start_date'] : '', date('Y-m-d'));
        $stopDate = CrmSupport::date(isset($p['stop_date']) ? $p['stop_date'] : '', date('Y-m-d'));

        switch ($this->action()) {
            case 'check':
                $stmt = $pdo->prepare("SELECT DATE(created_at) AS day, SUM(total) AS proceeds, AVG(total) AS a_b FROM checks WHERE uid = ? AND place_id = ? AND created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY) GROUP BY day ORDER BY day");
                $stmt->execute(array($uid, $place, $startDate, $stopDate));

                $rows = array();
                foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                    $rows[$row['day']] = array(
                        'proceeds' => (float)$row['proceeds'],
                        'a_b' => (float)$row['a_b'],
                    );
                }
                \mokko_json($rows);
                break;

            case 'sales':
                $stmt = $pdo->prepare("SELECT c.items FROM checks c WHERE c.uid = ? AND c.place_id = ? AND c.created_at >= ? AND c.created_at < DATE_ADD(?, INTERVAL 1 DAY)");
                $stmt->execute(array($uid, $place, $startDate, $stopDate));

                $totals = array();
                foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $check) {
                    $items = json_decode((string)$check['items'], true);
                    if (!is_array($items)) {
                        continue;
                    }
                    foreach ($items as $item) {
                        if (!is_array($item) || !isset($item[0])) {
                            continue;
                        }
                        $itemId = (int)$item[0];
                        $qty = isset($item[1]) ? (int)$item[1] : 1;
                        $price = isset($item[4]) ? (float)$item[4] : 0;

                        if (!isset($totals[$itemId])) {
                            $totals[$itemId] = array('name' => 'Item #' . $itemId, 'q' => 0, 'total' => 0);
                        }
                        $totals[$itemId]['q'] += $qty;
                        $totals[$itemId]['total'] += $price;
                    }
                }

                if (!empty($totals)) {
                    $ids = implode(',', array_map('intval', array_keys($totals)));
                    $stmt = $pdo->prepare("SELECT id, name FROM items WHERE uid = ? AND id IN ($ids)");
                    $stmt->execute(array($uid));
                    foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $item) {
                        if (isset($totals[(int)$item['id']])) {
                            $totals[(int)$item['id']]['name'] = $item['name'];
                        }
                    }
                }

                \mokko_json(array_values($totals));
                break;

            case 'staff':
                CrmSupport::shiftsReady($pdo);

                $stmt = $pdo->prepare("SELECT sh.uid, COALESCE(sh.staff_id, 0) AS staff_id,
                        SUM(sh.hours * 3600 + sh.min * 60 + sh.sec) AS secs,
                        SUM(CASE WHEN sh.hours * 3600 + sh.min * 60 + sh.sec >= 60 THEN 1 ELSE 0 END) AS shifts
                    FROM shifts sh
                    WHERE sh.uid = ? AND sh.place_id = ? AND sh.created_at >= ? AND sh.created_at < DATE_ADD(?, INTERVAL 1 DAY) AND sh.closed_at IS NOT NULL
                    GROUP BY sh.uid, COALESCE(sh.staff_id, 0)");
                $stmt->execute(array($uid, $place, $startDate, $stopDate));
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                // Батч-загрузка карточек вместо запроса на каждую смену.
                $staffIds = array();
                $ownerIds = array();
                foreach ($rows as $row) {
                    if ((int)$row['staff_id'] > 0) {
                        $staffIds[(int)$row['staff_id']] = true;
                    } else {
                        $ownerIds[(int)$row['uid']] = true;
                    }
                }

                $staffMap = array();
                if (!empty($staffIds)) {
                    $ids = array_keys($staffIds);
                    $in = implode(',', array_fill(0, count($ids), '?'));
                    $person = $pdo->prepare('SELECT id, name, lastname, email, rate FROM staff WHERE uid = ? AND id IN (' . $in . ')');
                    $person->execute(array_merge(array($uid), $ids));
                    foreach ($person->fetchAll(\PDO::FETCH_ASSOC) as $personRow) {
                        $staffMap[(int)$personRow['id']] = $personRow;
                    }
                }

                $userMap = array();
                if (!empty($ownerIds)) {
                    $ids = array_keys($ownerIds);
                    $in = implode(',', array_fill(0, count($ids), '?'));
                    $person = $pdo->prepare('SELECT id, name, lastname, email, NULL AS rate FROM users WHERE id IN (' . $in . ')');
                    $person->execute($ids);
                    foreach ($person->fetchAll(\PDO::FETCH_ASSOC) as $personRow) {
                        $userMap[(int)$personRow['id']] = $personRow;
                    }
                }

                // Ставка владельца ищется по e-mail в карточке сотрудника.
                $emails = array();
                foreach (array_merge($staffMap, $userMap) as $personRow) {
                    if (!empty($personRow['email'])) {
                        $emails[$personRow['email']] = true;
                    }
                }
                $rateMap = array();
                if (!empty($emails)) {
                    $emails = array_keys($emails);
                    $in = implode(',', array_fill(0, count($emails), '?'));
                    $rates = $pdo->prepare('SELECT email, rate FROM staff WHERE uid = ? AND email IN (' . $in . ')');
                    $rates->execute(array_merge(array($uid), $emails));
                    foreach ($rates->fetchAll(\PDO::FETCH_ASSOC) as $rateRow) {
                        if (!isset($rateMap[$rateRow['email']])) {
                            $rateMap[$rateRow['email']] = $rateRow['rate'];
                        }
                    }
                }

                $result = array();
                foreach ($rows as $row) {
                    $secs = (int)$row['secs'];
                    if ($secs < 60) {
                        continue;
                    }
                    $hours = $secs / 3600;
                    $staffId = (int)$row['staff_id'];

                    if ($staffId > 0) {
                        $personRow = isset($staffMap[$staffId]) ? $staffMap[$staffId] : null;
                    } else {
                        $personRow = isset($userMap[(int)$row['uid']]) ? $userMap[(int)$row['uid']] : null;
                    }

                    if (!$personRow && $staffId > 0) {
                        $personRow = array('name' => 'Удалённый сотрудник', 'lastname' => '', 'email' => '', 'rate' => 0);
                    }

                    $rate = ($personRow && $personRow['rate'] !== null) ? (float)$personRow['rate'] : 0.0;
                    if ($personRow && $personRow['rate'] === null && $personRow['email'] !== '') {
                        $rate = isset($rateMap[$personRow['email']]) ? (float)$rateMap[$personRow['email']] : 0.0;
                    }

                    $name = $personRow ? trim((string)$personRow['name']) : '';
                    $lastname = $personRow ? trim((string)$personRow['lastname']) : '';
                    if ($lastname !== '' && mb_stripos($name, $lastname, 0, 'UTF-8') === false) {
                        $name = trim($name . ' ' . $lastname);
                    }

                    $result[] = array(
                        'name' => $name !== '' ? $name : 'Без имени',
                        'h' => round($hours, 2),
                        'secs' => $secs,
                        'shifts' => (int)$row['shifts'],
                        'rate' => round($hours * $rate, 2),
                    );
                }

                \mokko_json($result);
                break;

            default:
                \mokko_json(array('error' => 'unknown action'));
        }
    }
}
