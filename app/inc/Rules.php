<?php

/**
 * Class responsible for content manipulation rules management
 * Classe responsável pelo gerenciamento de regras de manipulação de conteúdo
 * 
 * This class implements a rules system for different web domains,
 * allowing system behavior customization for each site.
 * Includes functionalities for paywall removal, specific elements,
 * cookie manipulation and custom code execution.
 * 
 * Esta classe implementa um sistema de regras para diferentes domínios web,
 * permitindo a personalização do comportamento do sistema para cada site.
 * Inclui funcionalidades para remoção de paywalls, elementos específicos,
 * manipulação de cookies e execução de códigos customizados.
 */
class Rules
{
    /**
     * Associative array containing specific rules for each domain
     * Array associativo contendo regras específicas para cada domínio
     * 
     * Possible configurations for each domain:
     * Configurações possíveis para cada domínio:
     * @var array
     */
    private $domainRules = DOMAIN_RULES;

    /**
     * Expanded global rules
     * Regras globais expandidas
     * @var array
     */
    private $globalRules = GLOBAL_RULES;

    /**
     * List of supported rule types
     * Lista de tipos de regras suportados
     * @var array
     */
    private $supportedRuleTypes = [
        'userAgent',
        'headers',
        'idElementRemove',
        'classElementRemove',
        'scriptTagRemove',
        'cookies',
        'classAttrRemove',
        'customCode',
        'excludeGlobalRules',
        'customStyle',
        'socialReferrer',
        'fetchStrategies',
        'fromGoogleBot',
        'removeElementsByTag',
        'removeCustomAttr'
    ];

    /**
     * Gets the base domain by removing www prefix
     * Obtém o domínio base removendo o prefixo www
     * 
     * @param string $domain Full domain / Domínio completo
     * @return string Base domain without www / Domínio base sem www
     */
    private function getBaseDomain($domain)
    {
        return preg_replace('/^www\./', '', $domain);
    }

    /**
     * Splits a domain into its constituent parts
     * Divide um domínio em suas partes constituintes
     * 
     * @param string $domain Domain to be split / Domínio a ser dividido
     * @return array Array with all possible domain combinations / Array com todas as combinações possíveis do domínio
     */
    private function getDomainParts($domain)
    {
        $domain = $this->getBaseDomain($domain);
        $parts = explode('.', $domain);

        $combinations = [];
        for ($i = 0; $i < count($parts) - 1; $i++) {
            $combinations[] = implode('.', array_slice($parts, $i));
        }

        usort($combinations, function ($a, $b) {
            return strlen($b) - strlen($a);
        });

        return $combinations;
    }

    /**
     * Gets specific rules for a domain
     * Obtém as regras específicas para um domínio
     * 
     * @param string $domain Domain to search rules for / Domínio para buscar regras
     * @return array|null Array with merged rules or null if not found / Array com regras mescladas ou null se não encontrar
     */
    public function getDomainRules($domain)
    {
        $domainParts = $this->getDomainParts($domain);

        foreach ($this->domainRules as $pattern => $rules) {
            if ($this->getBaseDomain($domain) === $this->getBaseDomain($pattern)) {
                return $this->mergeWithGlobalRules($rules);
            }
        }

        foreach ($domainParts as $part) {
            foreach ($this->domainRules as $pattern => $rules) {
                if ($part === $this->getBaseDomain($pattern)) {
                    return $this->mergeWithGlobalRules($rules);
                }
            }
        }

        // If no specific rules found, return only global rules
        // Se não encontrou regras específicas, retorna apenas as regras globais
        return $this->getGlobalRules();
    }

    /**
     * Merges domain-specific rules with global rules
     * Mescla regras específicas do domínio com regras globais
     * 
     * @param array $rules Domain-specific rules / Regras específicas do domínio
     * @return array Merged rules / Regras mescladas
     */
    private function mergeWithGlobalRules($rules)
    {
        $globalRules = $this->getGlobalRules();
        $mergedRules = [];

        // Process global rules exclusions
        // Processa as exclusões de regras globais
        $excludeGlobalRules = isset($rules['excludeGlobalRules']) ? $rules['excludeGlobalRules'] : [];
        unset($rules['excludeGlobalRules']); // Remove from rules array to avoid processing as normal rule / Remove do array de regras para não ser processado como regra normal

        // First, add all global rules except excluded ones
        // Primeiro, adiciona todas as regras globais, exceto as excluídas
        foreach ($globalRules as $ruleType => $globalTypeRules) {
            if (!in_array($ruleType, $this->supportedRuleTypes)) {
                continue;
            }

            if (isset($excludeGlobalRules[$ruleType])) {
                // If rule type is an associative array (like cookies or headers)
                // Se o tipo de regra é um array associativo (como cookies ou headers)
                if (is_array($globalTypeRules) && array_keys($globalTypeRules) !== range(0, count($globalTypeRules) - 1)) {
                    $mergedRules[$ruleType] = array_diff_key($globalTypeRules, array_flip($excludeGlobalRules[$ruleType]));
                } else {
                    // For simple arrays (like classElementRemove)
                    // Para arrays simples (como classElementRemove)
                    $mergedRules[$ruleType] = array_diff($globalTypeRules, $excludeGlobalRules[$ruleType]);
                }
            } else {
                $mergedRules[$ruleType] = $globalTypeRules;
            }
        }

        // Then, merge with domain-specific rules
        // Depois, mescla com as regras específicas do domínio
        foreach ($rules as $ruleType => $domainTypeRules) {
            if (!in_array($ruleType, $this->supportedRuleTypes)) {
                continue;
            }

            if (!isset($mergedRules[$ruleType])) {
                $mergedRules[$ruleType] = $domainTypeRules;
                continue;
            }

            // If rule type already exists, merge appropriately
            // Se o tipo de regra já existe, mescla apropriadamente
            if (in_array($ruleType, ['cookies', 'headers'])) {
                // For cookies and headers, preserve keys
                // Para cookies e headers, preserva as chaves
                $mergedRules[$ruleType] = array_merge($mergedRules[$ruleType], $domainTypeRules);
            } else {
                // For other types, merge as simple arrays
                // Para outros tipos, mescla como arrays simples
                $mergedRules[$ruleType] = array_values(array_unique(array_merge(
                    $mergedRules[$ruleType],
                    (array)$domainTypeRules
                )));
            }
        }

        return $mergedRules;
    }

    /**
     * Returns all global rules
     * Retorna todas as regras globais
     * 
     * @return array Array with all global rules / Array com todas as regras globais
     */
    public function getGlobalRules()
    {
        return $this->globalRules;
    }
}
