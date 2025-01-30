<?php

namespace Inc\URLAnalyzer;

use DOMDocument;
use DOMXPath;
use DOMElement;

class URLAnalyzerProcess extends URLAnalyzerBase
{
    private $error;

    public function __construct()
    {
        parent::__construct();
        $this->error = new URLAnalyzerError();
    }

    private function createDOM($content) {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        libxml_use_internal_errors(true);
        @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        return $dom;
    }

    public function processContent($content, $host, $url)
    {
        if (strlen($content) < 5120) {
            $this->error->throwError(self::ERROR_CONTENT_ERROR);
        }

        $dom = $this->createDOM($content);
        $xpath = new DOMXPath($dom);

        // Process all modifications in real-time
        $this->processCanonicalLinks($dom, $xpath, $url);
        $this->fixRelativeUrls($dom, $xpath, $url);
        $this->applyDomainRules($dom, $xpath, $host);
        $this->cleanInlineStyles($xpath);
        $this->addBrandBar($dom, $xpath);
        $this->addDebugBar($dom, $xpath);

        return $dom->saveHTML();
    }

    private function processCanonicalLinks($dom, $xpath, $url) 
    {
        $canonicalLinks = $xpath->query("//link[@rel='canonical']");
        if ($canonicalLinks !== false) {
            foreach ($canonicalLinks as $link) {
                if ($link->parentNode) {
                    $link->parentNode->removeChild($link);
                }
            }
        }

        $head = $xpath->query('//head')->item(0);
        if ($head) {
            $newCanonical = $dom->createElement('link');
            $newCanonical->setAttribute('rel', 'canonical');
            $newCanonical->setAttribute('href', $url);
            $head->appendChild($newCanonical);
        }
    }

    private function applyDomainRules($dom, $xpath, $host)
    {
        $domainRules = $this->getDomainRules($host);

        if (isset($domainRules['customStyle'])) {
            $styleElement = $dom->createElement('style');
            $styleElement->appendChild($dom->createTextNode($domainRules['customStyle']));
            $dom->getElementsByTagName('head')[0]->appendChild($styleElement);
            $this->activatedRules[] = 'customStyle';
        }

        if (isset($domainRules['customCode'])) {
            $scriptElement = $dom->createElement('script');
            $scriptElement->setAttribute('type', 'text/javascript');
            $scriptElement->appendChild($dom->createTextNode($domainRules['customCode']));
            $dom->getElementsByTagName('body')[0]->appendChild($scriptElement);
        }

        $this->removeUnwantedElements($dom, $xpath, $domainRules);
    }

    private function removeUnwantedElements($dom, $xpath, $domainRules)
    {
        if (isset($domainRules['classAttrRemove'])) {
            foreach ($domainRules['classAttrRemove'] as $class) {
                $elements = $xpath->query("//*[contains(@class, '$class')]");
                if ($elements !== false && $elements->length > 0) {
                    foreach ($elements as $element) {
                        $this->removeClassNames($element, [$class]);
                    }
                    $this->activatedRules[] = "classAttrRemove: $class";
                }
            }
        }

        if (isset($domainRules['removeElementsByTag'])) {
            $tagsToRemove = $domainRules['removeElementsByTag'];
            foreach ($tagsToRemove as $tag) {
                $tagElements = $xpath->query("//$tag");
                if ($tagElements !== false) {
                    foreach ($tagElements as $element) {
                        if ($element->parentNode) {
                            $element->parentNode->removeChild($element);
                        }
                    }
                    $this->activatedRules[] = "removeElementsByTag: $tag";
                }
            }
        }

        if (isset($domainRules['idElementRemove'])) {
            foreach ($domainRules['idElementRemove'] as $id) {
                $elements = $xpath->query("//*[@id='$id']");
                if ($elements !== false && $elements->length > 0) {
                    foreach ($elements as $element) {
                        if ($element->parentNode) {
                            $element->parentNode->removeChild($element);
                        }
                    }
                    $this->activatedRules[] = "idElementRemove: $id";
                }
            }
        }

        if (isset($domainRules['classElementRemove'])) {
            foreach ($domainRules['classElementRemove'] as $class) {
                $elements = $xpath->query("//*[contains(@class, '$class')]");
                if ($elements !== false && $elements->length > 0) {
                    foreach ($elements as $element) {
                        if ($element->parentNode) {
                            $element->parentNode->removeChild($element);
                        }
                    }
                    $this->activatedRules[] = "classElementRemove: $class";
                }
            }
        }

        if (isset($domainRules['scriptTagRemove'])) {
            foreach ($domainRules['scriptTagRemove'] as $script) {
                $scriptElements = $xpath->query("//script[contains(@src, '$script')] | //script[contains(text(), '$script')]");
                if ($scriptElements !== false && $scriptElements->length > 0) {
                    foreach ($scriptElements as $element) {
                        if ($element->parentNode) {
                            $element->parentNode->removeChild($element);
                        }
                    }
                    $this->activatedRules[] = "scriptTagRemove: $script";
                }

                $linkElements = $xpath->query("//link[@as='script' and contains(@href, '$script') and @type='application/javascript']");
                if ($linkElements !== false && $linkElements->length > 0) {
                    foreach ($linkElements as $element) {
                        if ($element->parentNode) {
                            $element->parentNode->removeChild($element);
                        }
                    }
                    $this->activatedRules[] = "scriptTagRemove: $script";
                }
            }
        }

        if (isset($domainRules['removeCustomAttr'])) {
            foreach ($domainRules['removeCustomAttr'] as $attrPattern) {
                if (strpos($attrPattern, '*') !== false) {
                    $elements = $xpath->query('//*');
                    if ($elements !== false) {
                        $pattern = '/^' . str_replace('*', '.*', $attrPattern) . '$/';
                        foreach ($elements as $element) {
                            if ($element->hasAttributes()) {
                                $attrs = [];
                                foreach ($element->attributes as $attr) {
                                    if (preg_match($pattern, $attr->name)) {
                                        $attrs[] = $attr->name;
                                    }
                                }
                                foreach ($attrs as $attr) {
                                    $element->removeAttribute($attr);
                                }
                            }
                        }
                        $this->activatedRules[] = "removeCustomAttr: $attrPattern";
                    }
                } else {
                    $elements = $xpath->query("//*[@$attrPattern]");
                    if ($elements !== false && $elements->length > 0) {
                        foreach ($elements as $element) {
                            $element->removeAttribute($attrPattern);
                        }
                        $this->activatedRules[] = "removeCustomAttr: $attrPattern";
                    }
                }
            }
        }
    }

    private function cleanInlineStyles($xpath)
    {
        $elements = $xpath->query("//*[@style]");
        if ($elements !== false) {
            foreach ($elements as $element) {
                if ($element instanceof DOMElement) {
                    $style = $element->getAttribute('style');
                    $style = preg_replace('/(max-height|height|overflow|position|display|visibility)\s*:\s*[^;]+;?/', '', $style);
                    $element->setAttribute('style', $style);
                }
            }
        }
    }

    private function addBrandBar($dom, $xpath)
    {
        $body = $xpath->query('//body')->item(0);
        if ($body) {
            $brandDiv = $dom->createElement('div');
            $brandDiv->setAttribute('style', 'z-index: 99999; position: fixed; top: 0; right: 1rem; background: rgba(37,99,235, 0.9); backdrop-filter: blur(8px); color: #fff; font-size: 13px; line-height: 1em; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); padding: 8px 12px; margin: 0px; overflow: hidden; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; font-family: Tahoma, sans-serif;');
            $brandHtml = $dom->createDocumentFragment();
            $brandHtml->appendXML('<a href="'.SITE_URL.'" style="color: #fff; text-decoration: none; font-weight: bold;" target="_blank">'.htmlspecialchars(SITE_DESCRIPTION).'</a>');
            $brandDiv->appendChild($brandHtml);
            $body->appendChild($brandDiv);
        }
    }

    private function addDebugBar($dom, $xpath)
    {
        if (defined('LOG_LEVEL') && LOG_LEVEL === 'DEBUG') {
            $body = $xpath->query('//body')->item(0);
            if ($body) {
                $debugDiv = $dom->createElement('div');
                $debugDiv->setAttribute('style', 'position: fixed; bottom: 1rem; right: 1rem; max-width: 400px; padding: 1rem; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(8px); border: 1px solid #e5e7eb; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); overflow: auto; max-height: 80vh; z-index: 9999; font-family: monospace; font-size: 13px; line-height: 1.4;');

                if (empty($this->activatedRules)) {
                    $ruleElement = $dom->createElement('div');
                    $ruleElement->textContent = 'No rules activated / Nenhuma regra ativada';
                    $debugDiv->appendChild($ruleElement);
                } else {
                    foreach ($this->activatedRules as $rule) {
                        $ruleElement = $dom->createElement('div');
                        $ruleElement->textContent = $rule;
                        $debugDiv->appendChild($ruleElement);
                    }
                }

                $body->appendChild($debugDiv);
            }
        }
    }

    private function removeClassNames($element, $classesToRemove)
    {
        if (!$element->hasAttribute('class')) {
            return;
        }

        $classes = explode(' ', $element->getAttribute('class'));
        $newClasses = array_filter($classes, function ($class) use ($classesToRemove) {
            return !in_array(trim($class), $classesToRemove);
        });

        if (empty($newClasses)) {
            $element->removeAttribute('class');
        } else {
            $element->setAttribute('class', implode(' ', $newClasses));
        }
    }

    private function fixRelativeUrls($dom, $xpath, $baseUrl)
    {
        $parsedBase = parse_url($baseUrl);
        $baseHost = $parsedBase['scheme'] . '://' . $parsedBase['host'];

        $elements = $xpath->query("//*[@src]");
        if ($elements !== false) {
            foreach ($elements as $element) {
                if ($element instanceof DOMElement) {
                    $src = $element->getAttribute('src');
                    if (strpos($src, 'base64') !== false) {
                        continue;
                    }
                    if (strpos($src, 'http') !== 0 && strpos($src, '//') !== 0) {
                        $src = ltrim($src, '/');
                        $element->setAttribute('src', $baseHost . '/' . $src);
                    }
                }
            }
        }

        $elements = $xpath->query("//*[@href]");
        if ($elements !== false) {
            foreach ($elements as $element) {
                if ($element instanceof DOMElement) {
                    $href = $element->getAttribute('href');
                    if (strpos($href, 'mailto:') === 0 ||
                        strpos($href, 'tel:') === 0 ||
                        strpos($href, 'javascript:') === 0 ||
                        strpos($href, '#') === 0) {
                        continue;
                    }
                    if (strpos($href, 'http') !== 0 && strpos($href, '//') !== 0) {
                        $href = ltrim($href, '/');
                        $element->setAttribute('href', $baseHost . '/' . $href);
                    }
                }
            }
        }
    }
}
