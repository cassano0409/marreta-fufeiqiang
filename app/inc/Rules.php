<?php

/**
 * Manages domain-specific content manipulation rules
 * Handles rule merging between global and domain-specific configurations
 * Supports multiple rule types for web content manipulation
 */
class Rules
{
    /** @var array Domain-specific rule configurations */
    private $domainRules = DOMAIN_RULES;

    /** @var array Expanded global rule set */
    private $globalRules = GLOBAL_RULES;

    /** @var array Supported rule types */
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
     * Extracts root domain by removing www prefix
     * @param string $domain Full domain name
     */
    private function getBaseDomain($domain)
    {
        return preg_replace('/^www\./', '', $domain);
    }

    /**
     * Generates domain variations for rule matching
     * @param string $domain Original domain
     * @return array Sorted domain combinations by length
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
     * Retrieves merged rules for a domain
     * @param string $domain Target domain
     * @return array|null Combined ruleset or global rules
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

        return $this->getGlobalRules();
    }

    /**
     * Combines domain rules with global configuration
     * @param array $rules Domain-specific rules
     */
    private function mergeWithGlobalRules($rules)
    {
        $globalRules = $this->getGlobalRules();
        $mergedRules = [];

        $excludeGlobalRules = $rules['excludeGlobalRules'] ?? [];
        unset($rules['excludeGlobalRules']);

        foreach ($globalRules as $ruleType => $globalTypeRules) {
            if (!in_array($ruleType, $this->supportedRuleTypes)) continue;

            if (isset($excludeGlobalRules[$ruleType])) {
                if (is_assoc_array($globalTypeRules)) {
                    $mergedRules[$ruleType] = array_diff_key($globalTypeRules, array_flip($excludeGlobalRules[$ruleType]));
                } else {
                    $mergedRules[$ruleType] = array_diff($globalTypeRules, $excludeGlobalRules[$ruleType]);
                }
            } else {
                $mergedRules[$ruleType] = $globalTypeRules;
            }
        }

        foreach ($rules as $ruleType => $domainTypeRules) {
            if (!in_array($ruleType, $this->supportedRuleTypes)) continue;

            if (!isset($mergedRules[$ruleType])) {
                $mergedRules[$ruleType] = $domainTypeRules;
                continue;
            }

            if (in_array($ruleType, ['cookies', 'headers'])) {
                $mergedRules[$ruleType] = array_merge($mergedRules[$ruleType], $domainTypeRules);
            } else {
                $mergedRules[$ruleType] = array_values(array_unique(array_merge(
                    $mergedRules[$ruleType],
                    (array)$domainTypeRules
                )));
            }
        }

        return $mergedRules;
    }

    /** @return array Global rule configuration */
    public function getGlobalRules()
    {
        return $this->globalRules;
    }
}

// Helper function for associative array check
function is_assoc_array($array) {
    return array_keys($array) !== range(0, count($array) - 1);
}