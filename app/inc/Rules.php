<?php
/**
 * Classe responsável pelo gerenciamento de regras de manipulação de conteúdo
 * 
 * Esta classe implementa um sistema de regras para diferentes domínios web,
 * permitindo a personalização do comportamento do sistema para cada site.
 * Inclui funcionalidades para remoção de paywalls, elementos específicos,
 * manipulação de cookies e execução de códigos customizados.
 */
class Rules {
    /**
     * Array associativo contendo regras específicas para cada domínio
     * 
     * Configurações possíveis para cada domínio:
     * @var array
     * 
     * - idElementRemove: IDs de elementos HTML que devem ser removidos
     * - classElementRemove: Classes de elementos HTML que devem ser removidos
     * - scriptTagRemove: Scripts que devem ser removidos
     * - cookies: Cookies que devem ser definidos ou removidos
     * - classAttrRemove: Classes que devem ser removidas de elementos
     * - clearStorage: Se deve limpar o storage do navegador
     * - customCode: Código JavaScript personalizado para execução
     * - excludeGlobalRules: Array de regras globais a serem excluídas
     * - userAgent: User Agent personalizado
     * - headers: Headers HTTP personalizados
     * - fixRelativeUrls: Habilita correção de URLs relativas
     */
    private $domainRules = DOMAIN_RULES;

    // Regras globais expandidas
    private $globalRules = GLOBAL_RULES;

    /**
     * Obtém o domínio base removendo o prefixo www
     * 
     * @param string $domain Domínio completo
     * @return string Domínio base sem www
     */
    private function getBaseDomain($domain) {
        return preg_replace('/^www\./', '', $domain);
    }

    /**
     * Divide um domínio em suas partes constituintes
     * 
     * @param string $domain Domínio a ser dividido
     * @return array Array com todas as combinações possíveis do domínio
     */
    private function getDomainParts($domain) {
        $domain = $this->getBaseDomain($domain);
        $parts = explode('.', $domain);
        
        $combinations = [];
        for ($i = 0; $i < count($parts) - 1; $i++) {
            $combinations[] = implode('.', array_slice($parts, $i));
        }
        
        usort($combinations, function($a, $b) {
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
    public function getDomainRules($domain) {
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
    private function mergeWithGlobalRules($rules) {
        $globalRules = $this->getGlobalRules();

        if (isset($rules['excludeGlobalRules']) && is_array($rules['excludeGlobalRules'])) {
            foreach ($rules['excludeGlobalRules'] as $ruleType => $categories) {
                if (isset($globalRules[$ruleType])) {
                    foreach ($categories as $category => $itemsToExclude) {
                        if (isset($globalRules[$ruleType][$category])) {
                            $globalRules[$ruleType][$category] = array_diff(
                                $globalRules[$ruleType][$category],
                                $itemsToExclude
                            );
                        }
                    }
                }
            }
        }

        foreach ($globalRules as $ruleType => $categories) {
            if (!isset($rules[$ruleType])) {
                $rules[$ruleType] = [];
            }
            foreach ($categories as $category => $items) {
                $rules[$ruleType] = array_merge($rules[$ruleType], $items);
            }
        }

        return $rules;
    }

    /**
     * Retorna todas as regras globais
     * 
     * @return array Array com todas as regras globais
     */
    public function getGlobalRules() {
        return $this->globalRules;
    }
}
