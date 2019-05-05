<?php
class Rk
{
    const SELECT_BY_STATUS = 'SELECT * FROM keywords WHERE status = ? ORDER BY created DESC LIMIT 100';
    const SELECT_BY_STATUS_ASC = 'SELECT * FROM keywords WHERE status = ? ORDER BY created ASC LIMIT 100';
    const SELECT_BY_KEYWORD = 'SELECT * FROM keywords WHERE keyword = ? ORDER BY created DESC LIMIT 100';
    const INSERT = 'INSERT INTO keywords VALUES (?, ?, ?, ?, ?, ?)';
    const UPDATE_STATUS = 'UPDATE keywords SET status = \'ACTIVE\', activated = ? WHERE id = ?';
    const UPDATE_STATUS2 = 'UPDATE keywords SET status = \'DONE\', done = ? WHERE id = ?';

    private $pdo;
    public function __construct()
    {
        $pdo = new PDO('sqlite:./_/rk.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec("CREATE TABLE IF NOT EXISTS keywords(
            id VARCHAR(100) PRIMARY KEY,
            keyword VARCHAR(50),
            status VARCHAR(10),
            created TIMESTAMP,
            activated TIMESTAMP,
            done TIMESTAMP
        )");
        $this->pdo = $pdo;
    }
    public function __destruct()
    {
        $this->pdo = null;
    }
    public function updateStatus(): void
    {
        $statement = $this->pdo->prepare(self::SELECT_BY_STATUS);
        $statement->execute(['ACTIVE']);
        foreach ($statement->fetchAll() as $keyword) {
            if (is_file('./output/' . $keyword['id'] . '.txt')) {
                $stmt = $this->pdo->prepare(self::UPDATE_STATUS2);
                $stmt->execute([date('Y-m-d H:i:s'), $keyword['id']]);
            }
        }
    }
    private function inActive(): bool
    {
        $statement = $this->pdo->prepare(self::SELECT_BY_STATUS);
        $statement->execute(['ACTIVE']);
        $result = $statement->fetchAll();

        return !empty($result);
    }
    public function push(string $k): void
    {
        $statement = $this->pdo->prepare(self::SELECT_BY_KEYWORD);
        $statement->execute([$k]);
        if (!empty($statement->fetchAll())) {
            return;
        }

        $statement = $this->pdo->prepare(self::INSERT);
        $now = date('Y-m-d H:i:s');
        $statement->execute([$this->rand(), $k, 'WAITING', $now, null, null]);
    }
    public function pop(): void
    {
        if ($this->inActive()) {
            return;
        }

        $statement = $this->pdo->prepare(self::SELECT_BY_STATUS_ASC);
        $statement->execute(['WAITING']);
        $result = $statement->fetchAll();
        if (empty($result)) {
            return;
        }

        exec(sprintf('node ./_/capture.js "%s" "%s" > /dev/null 2>&1 &', $result[0]['keyword'], $result[0]['id']));
        $statement = $this->pdo->prepare(self::UPDATE_STATUS);
        $statement->execute([date('Y-m-d H:i:s'), $result[0]['id']]);
    }
    public function list(): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM keywords ORDER BY created DESC');
        $statement->execute();
        $result = $statement->fetchAll();

        $statement = null;
        $pdo = null;
        return $result;
    }

    private function rand(): string 
    {
        return bin2hex(openssl_random_pseudo_bytes(32));
    }
}
$rk = new Rk();
if ('POST' === $_SERVER['REQUEST_METHOD'] && !empty($_POST['keyword'])) {
    $rk->push($_POST['keyword']);
}
$rk->updateStatus();
$rk->pop();

?>
<!DOCTYPE html>
<html lang='ja'>
<head>
  <meta charset="utf-8">
  <style>
    .form { margin-bottom: 2rem; }
    .form input[type="text"] { font-size: 1.05rem; padding: 0.2rem 0; width: 20rem; }
    .form button { font-size: 1.05rem; }
    table { border-collapse: collapse; border-spacing: 0; min-width: 600px; max-width: 1200px; }
    thead { background: #0a0; color: #fff; }
    tbody tr:nth-child(2n) { background: #dfd; }
    th, td { padding: 0.5rem 1rem; }
  </style>
</head>
<body>
  <section class='form'>
    <form method='post'>
      <input type='text' name='keyword' placeholder="keyword here.">
      <button type='submit'>add queue</button>
    </form>
  </section>
  <table>
    <thead>
      <tr>
        <th>Keyword</th>
        <th>Status</th>
        <th>Pushed</th>
        <th>Activate</th>
        <th>Done</th>
        <th>&nbsp;</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rk->list() as $k): ?>
      <tr>
        <td><?php echo htmlspecialchars($k['keyword']); ?></td>
        <td><?php echo $k['status']; ?></td>
        <td><?php echo $k['created']; ?></td>
        <td><?php echo $k['activated']; ?></td>
        <td><?php echo $k['done']; ?></td>
        <td>
          <?php if ($k['status'] === 'DONE'): ?>
          <a href='/output/<?php echo $k["id"]; ?>.txt'>Download</a>
          <?php else: ?>
          &nbsp;
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach;?>
    </tbody>
  </table>
</body>
</html>