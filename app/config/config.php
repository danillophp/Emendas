<?php

declare(strict_types=1);

/**
 * Configuração central da aplicação para ambiente HostGator.
 */
const APP_NAME = 'Sistema de Gestão de Emendas Governamentais';
const APP_ENV = 'production'; // production | local
const APP_DEBUG = false;

// Base de publicação em hospedagem compartilhada.
const APP_BASE_PATH = '/emendas';
const APP_URL = 'https://www.prefsade.com.br/emendas';
const BASE_URL = APP_URL; // alias solicitado para compatibilidade.

// Credenciais de produção (cPanel / phpMyAdmin).
const DB_HOST = 'localhost';
const DB_NAME = 'santo821_emenda';
const DB_USER = 'santo821_emenda';
const DB_PASS = 'php@3903.';
const DB_CHARSET = 'utf8mb4';

// Produção
const TIMEZONE = 'America/Sao_Paulo';
const LOG_PATH = BASE_PATH . '/storage/logs/app.log';

const MASTER_DEFAULT_EMAIL = 'master@emendas.local';
const MASTER_DEFAULT_PASSWORD = 'Master@123';

date_default_timezone_set(TIMEZONE);
