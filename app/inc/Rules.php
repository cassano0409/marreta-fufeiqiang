<?php

/**
 * Classe responsável pelo gerenciamento de regras de manipulação de conteúdo
 * 
 * Esta classe implementa um sistema de regras para diferentes domínios web,
 * permitindo a personalização do comportamento do sistema para cada site.
 * Inclui funcionalidades para remoção de paywalls, elementos específicos,
 * manipulação de cookies e execução de códigos customizados.
 */
class Rules
{
    /**
     * Array associativo contendo regras específicas para cada domínio
     * 
     * Configurações possíveis para cada domínio:
     * @var array
     */
    private $domainRules = DOMAIN_RULES;

    // Regras globais expandidas
    private $globalRules = GLOBAL_RULES;

    /**
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
        'useSelenium'
    ];


    /**
     * Obtém o domínio base removendo o prefixo www
     * 
     * @param string $domain Domínio completo
     * @return string Domínio base sem www
     */
    private function getBaseDomain($domain)
    {
        return preg_replace('/^www\./', '', $domain);
    }

    /**
     * Divide um domínio em suas partes constituintes
     * 
     * @param string $domain Domínio a ser dividido
     * @return array Array com todas as combinações possíveis do domínio
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
     * Obtém as regras específicas para um domínio
     * 
     * @param string $domain Domínio para buscar regras
     * @return array|null Array com regras mescladas ou null se não encontrar
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

        // Se não encontrou regras específicas, retorna apenas as regras globais
        return $this->getGlobalRules();
    }

    /**
     * Mescla regras específicas do domínio com regras globais
     * 
     * @param array $rules Regras específicas do domínio
     * @return array Regras mescladas
     */
    private function mergeWithGlobalRules($rules)
    {
        $globalRules = $this->getGlobalRules();
        $mergedRules = [];

        // Processa as exclusões de regras globais
        $excludeGlobalRules = isset($rules['excludeGlobalRules']) ? $rules['excludeGlobalRules'] : [];
        unset($rules['excludeGlobalRules']); // Remove do array de regras para não ser processado como regra normal

        // Primeiro, adiciona todas as regras globais, exceto as excluídas
        foreach ($globalRules as $ruleType => $globalTypeRules) {
            if (!in_array($ruleType, $this->supportedRuleTypes)) {
                continue;
            }

            if (isset($excludeGlobalRules[$ruleType])) {
                // Se o tipo de regra é um array associativo (como cookies ou headers)
                if (is_array($globalTypeRules) && array_keys($globalTypeRules) !== range(0, count($globalTypeRules) - 1)) {
                    $mergedRules[$ruleType] = array_diff_key($globalTypeRules, array_flip($excludeGlobalRules[$ruleType]));
                } else {
                    // Para arrays simples (como classElementRemove)
                    $mergedRules[$ruleType] = array_diff($globalTypeRules, $excludeGlobalRules[$ruleType]);
                }
            } else {
                $mergedRules[$ruleType] = $globalTypeRules;
            }
        }

        // Depois, mescla com as regras específicas do domínio
        foreach ($rules as $ruleType => $domainTypeRules) {
            if (!in_array($ruleType, $this->supportedRuleTypes)) {
                continue;
            }

            if (!isset($mergedRules[$ruleType])) {
                $mergedRules[$ruleType] = $domainTypeRules;
                continue;
            }

            // Se o tipo de regra já existe, mescla apropriadamente
            if (in_array($ruleType, ['cookies', 'headers'])) {
                // Para cookies e headers, preserva as chaves
                $mergedRules[$ruleType] = array_merge($mergedRules[$ruleType], $domainTypeRules);
            } else {
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
     * Retorna todas as regras globais
     * 
     * @return array Array com todas as regras globais
     */
    public function getGlobalRules()
    {
        return $this->globalRules;
    }
}
