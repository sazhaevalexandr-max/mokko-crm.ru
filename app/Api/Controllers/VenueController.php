<?php
declare(strict_types=1);

namespace Mokko\Api\Controllers;

use Mokko\Api\CrmSupport;

/**
 * Кофейни, склад и гости текущей точки.
 */
final class VenueController extends ApiController
{
    public function places(): void
    {
        $pdo = $this->pdo();
        $uid = $this->uid();
        $p = $this->input();

        switch ($this->action()) {
            case 'load':
                $stmt = $pdo->prepare('SELECT id, name, address, inn, kpp, ltd, phone FROM places WHERE user_id = ? ORDER BY id');
                $stmt->execute(array($uid));
                \mokko_json($stmt->fetchAll(\PDO::FETCH_ASSOC));
                break;

            case 'get':
                $id = (int)(isset($p['place']) ? $p['place'] : 0);
                $stmt = $pdo->prepare('SELECT id, name, address, inn, kpp, ltd, phone FROM places WHERE id = ? AND user_id = ? LIMIT 1');
                $stmt->execute(array($id, $uid));
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                \mokko_json($row ? $row : array('error' => 'not found'));
                break;

            case 'new':
                $name    = \mokko_str(isset($p['name']) ? $p['name'] : '', 255);
                $address = \mokko_str(isset($p['address']) ? $p['address'] : '', 255);
                $inn     = \mokko_str(isset($p['inn']) ? $p['inn'] : '', 20);
                $kpp     = \mokko_str(isset($p['kpp']) ? $p['kpp'] : '', 20);
                $ltd     = \mokko_str(isset($p['ltd']) ? $p['ltd'] : '', 255);
                $phone   = CrmSupport::phoneClean(\mokko_str(isset($p['phone']) ? $p['phone'] : '', 20));
                $inn     = preg_replace('/\s+/', '', $inn);
                $kpp     = preg_replace('/\s+/', '', $kpp);

                if ($name === '' || $address === '') {
                    \mokko_json(array('error' => 'name and address required'));
                }

                $err = CrmSupport::requisitesError($inn, $kpp, $phone);
                if ($err !== '') {
                    \mokko_json(array('error' => $err));
                }

                $stmt = $pdo->prepare('INSERT INTO places (user_id, name, address, inn, kpp, ltd, phone) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute(array($uid, $name, $address, $inn, $kpp, $ltd, $phone));

                \mokko_json(array(
                    'id' => (int)$pdo->lastInsertId(), 'name' => $name, 'address' => $address,
                    'inn' => $inn, 'kpp' => $kpp, 'ltd' => $ltd, 'phone' => $phone,
                ));
                break;

            case 'edit':
                $id      = (int)(isset($p['place']) ? $p['place'] : 0);
                $name    = \mokko_str(isset($p['name']) ? $p['name'] : '', 255);
                $address = \mokko_str(isset($p['address']) ? $p['address'] : '', 255);
                $inn     = \mokko_str(isset($p['inn']) ? $p['inn'] : '', 20);
                $kpp     = \mokko_str(isset($p['kpp']) ? $p['kpp'] : '', 20);
                $ltd     = \mokko_str(isset($p['ltd']) ? $p['ltd'] : '', 255);
                $phone   = CrmSupport::phoneClean(\mokko_str(isset($p['phone']) ? $p['phone'] : '', 20));
                $inn     = preg_replace('/\s+/', '', $inn);
                $kpp     = preg_replace('/\s+/', '', $kpp);

                if (!CrmSupport::placeOwned($pdo, $uid, $id)) {
                    \mokko_json(array('error' => 'not found'));
                }
                if ($name === '' || $address === '') {
                    \mokko_json(array('error' => 'name and address required'));
                }

                $err = CrmSupport::requisitesError($inn, $kpp, $phone);
                if ($err !== '') {
                    \mokko_json(array('error' => $err));
                }

                $stmt = $pdo->prepare('UPDATE places SET name = ?, address = ?, inn = ?, kpp = ?, ltd = ?, phone = ? WHERE id = ? AND user_id = ?');
                $stmt->execute(array($name, $address, $inn, $kpp, $ltd, $phone, $id, $uid));

                \mokko_json(array(
                    'Id' => $id, 'id' => $id, 'name' => $name, 'address' => $address,
                    'inn' => $inn, 'kpp' => $kpp, 'ltd' => $ltd, 'phone' => $phone,
                ));
                break;

            default:
                \mokko_json(array('error' => 'unknown action'));
        }
    }

    public function lager(): void
    {
        $pdo = $this->pdo();
        $uid = $this->uid();
        $p = $this->input();

        switch ($this->action()) {
            case 'load':
                $stmt = $pdo->prepare('SELECT id, name, em, active, mass FROM lager WHERE uid = ? ORDER BY id');
                $stmt->execute(array($uid));
                \mokko_json($stmt->fetchAll(\PDO::FETCH_ASSOC));
                break;

            case 'new':
                $places = $pdo->prepare('SELECT id FROM places WHERE user_id = ?');
                $places->execute(array($uid));
                $massArr = array();
                foreach ($places->fetchAll(\PDO::FETCH_ASSOC) as $place) {
                    $massArr[] = array('place' => (int)$place['id'], 'mass' => 0);
                }
                if (empty($massArr)) {
                    $massArr[] = array('place' => 0, 'mass' => 0);
                }
                $massJson = json_encode($massArr);

                $stmt = $pdo->prepare('INSERT INTO lager (uid, name, em, active, mass) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute(array($uid, 'Название', 1, 1, $massJson));

                \mokko_json(array('Id' => (int)$pdo->lastInsertId(), 'mass' => $massJson));
                break;

            case 'edit':
                $id = (int)(isset($p['id']) ? $p['id'] : 0);
                $name = \mokko_str(isset($p['name']) ? $p['name'] : '', 255);
                $pdo->prepare('UPDATE lager SET name = ?, em = ? WHERE id = ? AND uid = ?')
                    ->execute(array($name, (int)(isset($p['em']) ? $p['em'] : 1), $id, $uid));
                \mokko_json(array('success' => true));
                break;

            case 'add':
            case 'substract':
                $id = (int)(isset($p['id']) ? $p['id'] : 0);
                $place = (int)(isset($p['place']) ? $p['place'] : 0);
                $diff = (float)(isset($p['mass']) ? $p['mass'] : 0);

                if (!CrmSupport::placeOwned($pdo, $uid, $place)) {
                    \mokko_json(array('error' => 'invalid place'));
                }

                $row = $pdo->prepare('SELECT mass FROM lager WHERE id = ? AND uid = ? LIMIT 1');
                $row->execute(array($id, $uid));
                $record = $row->fetch(\PDO::FETCH_ASSOC);
                if (!$record) {
                    \mokko_json(array('error' => 'not found'));
                }

                $massArr = json_decode((string)$record['mass'], true);
                if (!is_array($massArr)) {
                    $massArr = array();
                }

                $found = false;
                foreach ($massArr as &$massRow) {
                    if ((int)$massRow['place'] === $place) {
                        $massRow['mass'] = ($this->action() === 'add')
                            ? ($massRow['mass'] + $diff)
                            : ($massRow['mass'] - $diff);
                        $found = true;
                        break;
                    }
                }
                unset($massRow);

                if (!$found) {
                    $massArr[] = array(
                        'place' => $place,
                        'mass' => ($this->action() === 'add') ? $diff : -$diff,
                    );
                }

                $massJson = json_encode($massArr);
                $pdo->prepare('UPDATE lager SET mass = ? WHERE id = ? AND uid = ?')->execute(array($massJson, $id, $uid));

                \mokko_json(array('mass' => $massJson));
                break;

            case 'remove':
                $id = (int)(isset($p['id']) ? $p['id'] : 0);
                $pdo->prepare('UPDATE lager SET active = 0 WHERE id = ? AND uid = ?')->execute(array($id, $uid));
                \mokko_json(array('success' => true));
                break;

            default:
                \mokko_json(array('error' => 'unknown action'));
        }
    }

    public function guests(): void
    {
        $pdo = $this->pdo();
        $uid = $this->uid();
        $p = $this->input();

        switch ($this->action()) {
            case 'load':
                $place = (int)(isset($p['place']) ? $p['place'] : 0);
                $stmt = $pdo->prepare('SELECT id, name, phone, items, price FROM guests WHERE uid = ? AND place_id = ? AND status = "open" ORDER BY id DESC');
                $stmt->execute(array($uid, $place));
                \mokko_json($stmt->fetchAll(\PDO::FETCH_ASSOC));
                break;

            case 'new':
                $place = (int)(isset($p['place']) ? $p['place'] : 0);
                $name = \mokko_str(isset($p['name']) ? $p['name'] : '', 255);

                if (!CrmSupport::placeOwned($pdo, $uid, $place)) {
                    \mokko_json(array('error' => 'invalid place'));
                }

                $stmt = $pdo->prepare('INSERT INTO guests (uid, place_id, name, items, price, status) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute(array($uid, $place, $name, '[]', 0, 'open'));

                \mokko_json(array(
                    'Id' => (int)$pdo->lastInsertId(), 'name' => $name, 'phone' => '',
                    'items' => '[]', 'price' => 0,
                ));
                break;

            case 'edit':
                $id = (int)(isset($p['id']) ? $p['id'] : 0);
                $items = isset($p['items']) ? $p['items'] : '[]';
                $price = max(0, (float)(isset($p['price']) ? $p['price'] : 0));

                if (is_array($items)) {
                    $items = json_encode($items, JSON_UNESCAPED_UNICODE);
                } elseif (!is_string($items)) {
                    $items = '[]';
                }
                if (strlen($items) > 65535) {
                    \mokko_json(array('error' => 'too large'));
                }

                $pdo->prepare('UPDATE guests SET items = ?, price = ? WHERE id = ? AND uid = ?')
                    ->execute(array($items, $price, $id, $uid));
                \mokko_json(array('success' => true));
                break;

            case 'update':
                $id = (int)(isset($p['id']) ? $p['id'] : 0);
                $name = \mokko_str(isset($p['name']) ? $p['name'] : '', 255);
                $pdo->prepare('UPDATE guests SET name = ? WHERE id = ? AND uid = ?')->execute(array($name, $id, $uid));
                \mokko_json(array('success' => true));
                break;

            case 'get':
                $id = (int)(isset($p['id']) ? $p['id'] : 0);
                $stmt = $pdo->prepare('SELECT id, name, phone, items, price FROM guests WHERE id = ? AND uid = ? LIMIT 1');
                $stmt->execute(array($id, $uid));
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                \mokko_json($row ? $row : array('error' => 'not found'));
                break;

            default:
                \mokko_json(array('error' => 'unknown action'));
        }
    }
}
