<?php
require_once __DIR__ . '/../../../app/bootstrap.php';$user=api_user();$token=bearer_token();db()->prepare('DELETE FROM api_tokens WHERE token_hash=:h')->execute(['h'=>hash('sha256',(string)$token)]);api_response(['success'=>true,'mensaje'=>'Sesión cerrada.']);
