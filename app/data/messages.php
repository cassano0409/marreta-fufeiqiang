<?php

/**
 * Mensagens do sistema
 * 
 * Array associativo contendo todas as mensagens de erro e avisos
 * que podem ser exibidas ao usuário durante a execução do sistema
 */
return [
    'BLOCKED_DOMAIN' => [
        'message' => 'Este domínio está bloqueado para extração.',
        'type' => 'error'
    ],
    'DNS_FAILURE' => [
        'message' => 'Falha ao resolver DNS para o domínio. Verifique se a URL está correta.',
        'type' => 'warning'
    ],
    'HTTP_ERROR' => [
        'message' => 'O servidor retornou um erro ao tentar acessar a página. Tente novamente mais tarde.',
        'type' => 'warning'
    ],
    'CONNECTION_ERROR' => [
        'message' => 'Erro ao conectar com o servidor. Verifique sua conexão e tente novamente.',
        'type' => 'warning'
    ],
    'CONTENT_ERROR' => [
        'message' => 'Não foi possível obter o conteúdo. Tente usar os serviços de arquivo.',
        'type' => 'warning'
    ],
    'INVALID_URL' => [
        'message' => 'Formato de URL inválido',
        'type' => 'error'
    ],
    'NOT_FOUND' => [
        'message' => 'Página não encontrada',
        'type' => 'error'
    ],
    'GENERIC_ERROR' => [
        'message' => 'Ocorreu um erro ao processar sua solicitação.',
        'type' => 'warning'
    ]
];
