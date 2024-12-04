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
        'customCode'
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

        return null;
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
        $mergedRules = $rules;

        // Processa excludeGlobalRules primeiro
        $excludedRules = [];
        if (isset($rules['excludeGlobalRules']) && is_array($rules['excludeGlobalRules'])) {
            foreach ($rules['excludeGlobalRules'] as $ruleType => $excluded) {
                if (isset($excluded) && is_array($excluded)) {
                    foreach ($excluded as $category => $items) {
                        $excludedRules[$ruleType] = array_merge(
                            $excludedRules[$ruleType] ?? [],
                            (array)$items
                        );
                    }
                }
            }
        }

        // Mescla cada tipo de regra suportado
        foreach ($this->supportedRuleTypes as $ruleType) {
            if (isset($globalRules[$ruleType])) {
                if (!isset($mergedRules[$ruleType])) {
                    $mergedRules[$ruleType] = [];
                }

                // Garante que estamos trabalhando com arrays
                $domainTypeRules = (array)$mergedRules[$ruleType];
                $globalTypeRules = (array)$globalRules[$ruleType];

                // Aplica exclusões se existirem para este tipo
                if (isset($excludedRules[$ruleType])) {
                    $globalTypeRules = array_diff($globalTypeRules, $excludedRules[$ruleType]);
                }

                // Mescla as regras
                if (in_array($ruleType, ['cookies', 'headers'])) {
                    // Para cookies e headers, preserva as chaves
                    $mergedRules[$ruleType] = array_merge($globalTypeRules, $domainTypeRules);
                } else {
                    // Para outros tipos, mescla como arrays simples
                    $mergedRules[$ruleType] = array_values(array_unique(array_merge(
                        $domainTypeRules,
                        $globalTypeRules
                    )));
                }
            }
        }

        // Remove excludeGlobalRules do resultado final
        unset($mergedRules['excludeGlobalRules']);

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
