<?php
declare(strict_types=1);

header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
header('X-Content-Type-Options: nosniff');

const SEED = __DIR__ . '/beneficios.json';
$storage = getenv('OZZY_BENEFITS_DIR') ?: dirname(__DIR__) . '/ozzybox-benefits';
$root = str_replace('\\', '/', __DIR__);
$resolved = realpath($storage);
if ($resolved !== false) {
    $dir = str_replace('\\', '/', $resolved);
    if (str_starts_with(strtolower($dir) . '/', strtolower($root) . '/')) {
        $resolved = false;
    }
}

function platforms(string|false $dir): array
{
    $file = $dir !== false ? $dir . '/platforms.json' : '';
    $data = file_get_contents(is_file($file) ? $file : SEED);
    return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
}

function save(string $dir, callable $change): void
{
    $lock = fopen($dir . '/platforms.lock', 'c');
    if (!$lock) throw new RuntimeException('lock');
    try {
        if (!flock($lock, LOCK_EX)) throw new RuntimeException('lock');
        $updated = $change(platforms($dir));
        $tmp = tempnam($dir, 'benefits-');
        if ($tmp === false) throw new RuntimeException('temp');
        try {
            $json = json_encode(array_values($updated), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            if (file_put_contents($tmp, $json) !== strlen($json) || !rename($tmp, $dir . '/platforms.json')) {
                throw new RuntimeException('write');
            }
        } finally {
            if (is_file($tmp)) unlink($tmp);
        }
    } finally {
        fclose($lock);
    }
}

function post(string $key): string
{
    return is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
}

function httpsUrl(string $value): bool
{
    return filter_var($value, FILTER_VALIDATE_URL) !== false
        && str_starts_with($value, 'https://')
        && parse_url($value, PHP_URL_USER) === null;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (($_GET['admin'] ?? null) !== '1') {
    header('Content-Type: application/json; charset=utf-8');
    if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
        header('Allow: GET, HEAD');
        http_response_code(405);
        exit;
    }
    try {
        echo json_encode(array_values(array_filter(platforms($resolved), fn($p) => $p['accepted'])), JSON_THROW_ON_ERROR);
    } catch (Throwable $error) {
        error_log($error->getMessage());
        http_response_code(503);
        echo '{"error":"Convênios indisponíveis"}';
    }
    exit;
}

header('Content-Type: text/html; charset=utf-8');
header("Content-Security-Policy: default-src 'none'; style-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
header('Referrer-Policy: no-referrer');
$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (!$https && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Abra o painel pelo endereço HTTPS da academia.');
}
$hashFile = $resolved === false ? '' : $resolved . '/admin.hash';
$hash = is_readable($hashFile) ? trim(file_get_contents($hashFile)) : '';
if (!password_get_info($hash)['algo']) {
    http_response_code(503);
    exit('Painel indisponível. Configure a senha de administração conforme o README do projeto.');
}

ini_set('session.use_strict_mode', '1');
session_name('ozzybox_admin');
session_set_cookie_params(['secure' => $https, 'httponly' => true, 'samesite' => 'Strict']);
session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$authenticated = ($_SESSION['owner'] ?? '') === hash('sha256', $hash);
$notice = $_SESSION['notice'] ?? '';
unset($_SESSION['notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action');
    if (!hash_equals($_SESSION['csrf'], post('csrf')) || (!$authenticated && $action !== 'login')) {
        http_response_code(403);
        exit('Ação não autorizada. Volte ao painel e tente novamente.');
    }
    try {
        switch ($action) {
            case 'login':
                if (!password_verify(post('password'), $hash)) {
                    usleep(400000);
                    throw new InvalidArgumentException('Senha incorreta.');
                }
                session_regenerate_id(true);
                $_SESSION = ['owner' => hash('sha256', $hash), 'csrf' => bin2hex(random_bytes(32))];
                break;
            case 'logout':
                $_SESSION = [];
                session_destroy();
                break;
            case 'save':
                $id = post('id');
                $name = post('name');
                $aliases = post('aliases');
                $logo = post('logo');
                $url = post('url');
                $accepted = post('accepted') === '1';
                if ($name === '' || strlen($name) > 160 || strlen($aliases) > 360) {
                    throw new InvalidArgumentException('Preencha o nome e confira o tamanho dos nomes de pesquisa.');
                }
                $localLogo = preg_match('~\Aassets/beneficios/[a-z0-9_-]+\.(svg|png|webp|jpe?g)\z~i', $logo)
                    && is_file(__DIR__ . '/' . $logo);
                if (strlen($logo) > 2000 || (!$localLogo && !httpsUrl($logo))
                    || strlen($url) > 2000 || ($url !== '' && !httpsUrl($url))) {
                    throw new InvalidArgumentException('Use o endereço HTTPS do logotipo oficial e, se informar um link, use HTTPS.');
                }
                save($resolved, function (array $items) use ($id, $name, $aliases, $logo, $url, $accepted): array {
                    foreach ($items as $i => $item) {
                        if ($item['id'] === $id) {
                            $items[$i] = compact('id', 'name', 'aliases', 'logo', 'url', 'accepted');
                            return $items;
                        }
                    }
                    if ($id !== '') throw new InvalidArgumentException('Este convênio já foi removido.');
                    $id = bin2hex(random_bytes(12));
                    $items[] = compact('id', 'name', 'aliases', 'logo', 'url', 'accepted');
                    return $items;
                });
                $_SESSION['notice'] = 'Convênio salvo.';
                break;
            case 'delete':
                $id = post('id');
                save($resolved, fn($items) => array_filter($items, fn($item) => $item['id'] !== $id));
                $_SESSION['notice'] = 'Convênio removido.';
                break;
            default:
                throw new InvalidArgumentException('Ação inválida.');
        }
    } catch (InvalidArgumentException $error) {
        $_SESSION['notice'] = $error->getMessage();
    } catch (Throwable $error) {
        error_log($error->getMessage());
        $_SESSION['notice'] = 'Não foi possível salvar. Confira a configuração do painel.';
    }
    header('Location: convenios.php?admin=1', true, 303);
    exit;
}

$csrf = h($_SESSION['csrf']);
$empty = ['id' => '', 'name' => '', 'aliases' => '', 'logo' => '', 'url' => '', 'accepted' => false];
$items = $authenticated ? [...platforms($resolved), $empty] : [];
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gerenciar convênios | OzzyBox</title>
  <link rel="stylesheet" href="beneficios-admin.css">
</head>
<body>
<main>
  <a href="./#convenios">← Voltar aos convênios</a>
  <h1>Gerenciar convênios</h1>
  <?php if ($notice !== ''): ?><p class="notice" role="status"><?= h($notice) ?></p><?php endif; ?>
  <?php if (!$authenticated): ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= $csrf ?>">
    <label>Senha administrativa <input type="password" name="password" autocomplete="current-password" required></label>
    <button name="action" value="login">Entrar</button>
  </form>
  <?php else: ?>
  <p>Só aparecem no site as plataformas marcadas como aceitas.</p>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= $csrf ?>">
    <button class="secondary" name="action" value="logout">Sair</button>
  </form>
  <div class="cards">
  <?php foreach ($items as $item): ?>
    <article>
      <h2><?= h($item['name'] ?: 'Adicionar plataforma') ?></h2>
      <?php if ($item['id'] !== ''): ?>
      <p><?= $item['accepted'] ? 'Visível no site' : 'Oculto no site' ?></p>
      <?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="id" value="<?= h($item['id']) ?>">
        <label>Nome <input name="name" value="<?= h($item['name']) ?>" maxlength="80" required></label>
        <label>Outros nomes para busca, separados por vírgula <input name="aliases" value="<?= h($item['aliases']) ?>" maxlength="180" placeholder="Ex.: Gympass"></label>
        <label>Logotipo oficial (endereço HTTPS) <input name="logo" value="<?= h($item['logo']) ?>" maxlength="2000" required></label>
        <label>Link oficial (opcional) <input type="url" name="url" value="<?= h($item['url']) ?>" maxlength="2000"></label>
        <label class="check"><input type="checkbox" name="accepted" value="1" <?= $item['accepted'] ? 'checked' : '' ?>> Aceito na OzzyBox</label>
        <div class="actions">
          <button name="action" value="save">Salvar</button>
          <?php if ($item['id'] !== ''): ?><button class="secondary" name="action" value="delete" formnovalidate>Remover</button><?php endif; ?>
        </div>
      </form>
    </article>
  <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>
</body>
</html>
