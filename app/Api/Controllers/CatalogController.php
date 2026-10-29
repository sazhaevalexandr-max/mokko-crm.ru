<?php
declare(strict_types=1);

namespace Mokko\Api\Controllers;

use Mokko\Api\PlanLimits;

/**
 * Меню: категории, массовая загрузка позиций и CRUD одной позиции.
 */
final class CatalogController extends ApiController
{
    public function category(): void
    {
        $pdo = $this->pdo();
        $uid = $this->uid();
        $p = $this->input();

        switch ($this->action()) {
            case 'load':
                $stmt = $pdo->prepare('SELECT id, name, status FROM categories WHERE uid = ? ORDER BY id');
                $stmt->execute(array($uid));
                \mokko_json($stmt->fetchAll(\PDO::FETCH_ASSOC));
                break;

            case 'new':
                $name = \mokko_str(isset($p['name']) ? $p['name'] : 'Название', 255);
                $status = (isset($p['status']) && $p['status'] === 'off') ? 'off' : 'on';
                $name = ($name !== '' ? $name : 'Название');

                $countStmt = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE uid = ?');
                $countStmt->execute(array($uid));
                if ((int)$countStmt->fetchColumn() >= PlanLimits::CATEGORIES) {
                    \mokko_json(array('error' => 'Лимит тарифа: не более ' . PlanLimits::CATEGORIES . ' категорий меню.'));
                }

                $stmt = $pdo->prepare('INSERT INTO categories (uid, name, status) VALUES (?, ?, ?)');
                $stmt->execute(array($uid, $name, $status));

                \mokko_json(array('Id' => (int)$pdo->lastInsertId(), 'name' => $name, 'status' => $status));
                break;

            case 'edit':
                $id = (int)(isset($p['category']) ? $p['category'] : 0);
                $name = \mokko_str(isset($p['name']) ? $p['name'] : '', 255);
                $status = (isset($p['status']) && $p['status'] === 'off') ? 'off' : 'on';

                $pdo->prepare('UPDATE categories SET name = ?, status = ? WHERE id = ? AND uid = ?')
                    ->execute(array($name, $status, $id, $uid));
                \mokko_json(array('name' => $name, 'status' => $status));
                break;

            case 'del':
                $id = (int)(isset($p['category']) ? $p['category'] : 0);
                $pdo->prepare('DELETE FROM categories WHERE id = ? AND uid = ?')->execute(array($id, $uid));
                $pdo->prepare('DELETE FROM items WHERE category_id = ? AND uid = ?')->execute(array($id, $uid));
                \mokko_json(array('success' => true));
                break;

            case 'status':
                $id = (int)(isset($p['category']) ? $p['category'] : 0);
                $status = (isset($p['status']) && $p['status'] === 'off') ? 'off' : 'on';
                $pdo->prepare('UPDATE categories SET status = ? WHERE id = ? AND uid = ?')
                    ->execute(array($status, $id, $uid));
                \mokko_json(array('success' => true));
                break;

            default:
                \mokko_json(array('error' => 'unknown action'));
        }
    }

    public function items(): void
    {
        $pdo = $this->pdo();
        $uid = $this->uid();
        $p = $this->input();

        if ($this->action() !== 'load') {
            \mokko_json(array('error' => 'unknown action'));
        }

        $category = isset($p['category']) ? $p['category'] : 'all';

        if ($category === 'all') {
            $stmt = $pdo->prepare('SELECT i.id, i.name, i.price, i.mass, i.em, i.category_id AS type FROM items i WHERE i.uid = ? ORDER BY i.id');
            $stmt->execute(array($uid));
        } else {
            $stmt = $pdo->prepare('SELECT i.id, i.name, i.price, i.mass, i.em, i.category_id AS type FROM items i WHERE i.uid = ? AND i.category_id = ? ORDER BY i.id');
            $stmt->execute(array($uid, (int)$category));
        }

        \mokko_json($stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function item(): void
    {
        $pdo = $this->pdo();
        $uid = $this->uid();
        $p = $this->input();

        switch ($this->action()) {
            case 'new':
                $countStmt = $pdo->prepare('SELECT COUNT(*) FROM items WHERE uid = ?');
                $countStmt->execute(array($uid));
                if ((int)$countStmt->fetchColumn() >= PlanLimits::ITEMS) {
                    \mokko_json(array('error' => 'Лимит тарифа: не более ' . PlanLimits::ITEMS . ' позиций меню.'));
                }

                $category = (int)(isset($p['category']) ? $p['category'] : 0);
                $stmt = $pdo->prepare('INSERT INTO items (uid, category_id, name, price, mass, em, ingredients) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute(array($uid, $category, 'Название', 0, 0, 1, '[]'));

                \mokko_json(array('Id' => (int)$pdo->lastInsertId(), 'mass' => 0, 'em' => 1));
                break;

            case 'edit':
                $id = (int)(isset($p['id']) ? $p['id'] : 0);
                $name = \mokko_str(isset($p['name']) ? $p['name'] : '', 255);
                $price = max(0, (float)(isset($p['price']) ? $p['price'] : 0));
                $mass = max(0, (float)(isset($p['mass']) ? $p['mass'] : 0));
                $em = (int)(isset($p['em']) ? $p['em'] : 1);
                $ingredients = isset($p['ingredients']) ? $p['ingredients'] : array();
                if (!is_array($ingredients)) {
                    $ingredients = array();
                }

                $owned = $pdo->prepare('SELECT id FROM items WHERE id = ? AND uid = ? LIMIT 1');
                $owned->execute(array($id, $uid));
                if (!$owned->fetch(\PDO::FETCH_ASSOC)) {
                    \mokko_json(array('error' => 'not found'));
                }

                $stmt = $pdo->prepare('UPDATE items SET category_id = ?, name = ?, price = ?, mass = ?, em = ?, ingredients = ? WHERE id = ? AND uid = ?');
                $stmt->execute(array(
                    (int)(isset($p['category']) ? $p['category'] : 0),
                    $name, $price, $mass, $em,
                    json_encode($ingredients, JSON_UNESCAPED_UNICODE),
                    $id, $uid,
                ));

                \mokko_json(array('Id' => $id, 'name' => $name, 'price' => $price, 'mass' => $mass, 'em' => $em));
                break;

            case 'del':
                $id = (int)(isset($p['id']) ? $p['id'] : 0);
                $pdo->prepare('DELETE FROM items WHERE id = ? AND uid = ?')->execute(array($id, $uid));
                \mokko_json(array('success' => true));
                break;

            case 'get':
                $id = (int)(isset($p['id']) ? $p['id'] : 0);
                $stmt = $pdo->prepare('SELECT id, name, price, mass, em, ingredients FROM items WHERE id = ? AND uid = ? LIMIT 1');
                $stmt->execute(array($id, $uid));
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if (!$row) {
                    \mokko_json(array('error' => 'not found'));
                }
                \mokko_json($row);
                break;

            default:
                \mokko_json(array('error' => 'unknown action'));
        }
    }
}
