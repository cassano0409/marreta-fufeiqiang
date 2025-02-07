<?php

/**
 * Processes and modifies HTML content
 * Handles DOM changes and content rules
 */

namespace Inc\URLAnalyzer;

use DOMDocument;
use DOMXPath;
use DOMElement;

class URLAnalyzerProcess extends URLAnalyzerBase
{
    /** @var URLAnalyzerError Handler for throwing formatted errors */
    private $error;

    public function __construct()
    {
        parent::__construct();
        $this->error = new URLAnalyzerError();
    }

    /** Creates DOM from HTML content */
    private function createDOM($content)
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        libxml_use_internal_errors(true);
        @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        return $dom;
    }

    /** 
     * Processes and modifies HTML content
     * Applies rules and fixes URLs
     */
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
        $this->addBrandBar($dom, $xpath, $url);
        $this->addDebugBar($dom, $xpath);

        return $dom->saveHTML();
    }

    /** Updates canonical link tags */
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

    /** Applies domain rules to content */
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

    /** Removes unwanted elements by rules */
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

    /** Cleans problematic inline styles */
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

    /** Adds branded bar to page */
    private function addBrandBar($dom, $xpath, $url)
    {
        $body = $xpath->query('//body')->item(0);
        if ($body) {
            $brandDiv = $dom->createElement('div');
            $brandDiv->setAttribute('style', 'z-index: 99999; position: fixed; top: 0; right: 1rem; display: flex; gap: 8px;');
            $brandHtml = $dom->createDocumentFragment();
            $brandHtml->appendXML('<a href="' . htmlspecialchars($url) . '" style="color: #fff; text-decoration: none; font-weight: bold; background: rgba(37,99,235, 0.9); box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); padding: 6px 10px; margin: 0px; overflow: hidden; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;" target="_blank"><svg xmlns="http://www.w3.org/2000/svg" fill="#fff" viewBox="0 0 16 16" width="20" height="20"><path d="M4.715 6.542 3.343 7.914a3 3 0 1 0 4.243 4.243l1.828-1.829A3 3 0 0 0 8.586 5.5L8 6.086a1 1 0 0 0-.154.199 2 2 0 0 1 .861 3.337L6.88 11.45a2 2 0 1 1-2.83-2.83l.793-.792a4 4 0 0 1-.128-1.287z"/><path d="M6.586 4.672A3 3 0 0 0 7.414 9.5l.775-.776a2 2 0 0 1-.896-3.346L9.12 3.55a2 2 0 1 1 2.83 2.83l-.793.792c.112.42.155.855.128 1.287l1.372-1.372a3 3 0 1 0-4.243-4.243z"/></svg></a>');
            $brandDiv->appendChild($brandHtml);
            $brandHtml->appendXML('<a href="' . htmlspecialchars(SITE_URL) . '" style="color: #fff; text-decoration: none; font-weight: bold; background: rgba(37,99,235, 0.9); box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); padding: 6px 10px; margin: 0px; overflow: hidden; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;" target="_blank"><svg xmlns="http://www.w3.org/2000/svg" fill="#fff" viewBox="0 0 640 512" width="20" height="20"><path d="m283.9 378.6 18.3-60.1c18-4.1 34.2-16 43.1-33.8l64-128c10.5-21.1 8.4-45.2-3.7-63.6l52.7-76.6c3.7-5.4 10.4-8 16.7-6.5s11.2 6.7 12.2 13.1l16.2 104.1 105.1-7.4c6.5-.5 12.7 3.1 15.5 9s1.8 12.9-2.6 17.8L550.1 224l71.3 77.5c4.4 4.8 5.5 11.9 2.6 17.8s-9 9.5-15.5 9l-105.1-7.4L487.3 425c-1 6.5-5.9 11.7-12.2 13.1s-13-1.1-16.7-6.5l-59.7-86.7-91.4 52.2c-5.7 3.3-12.8 2.7-17.9-1.4s-7.2-10.9-5.3-17.2zm28.3-101.7c-9.3 10.9-25.2 14.4-38.6 7.7l-65.9-32.9-85.7-42.9-104.3-52.2c-15.8-7.9-22.2-27.1-14.3-42.9l40-80C48.8 22.8 59.9 16 72 16h120c5 0 9.9 1.2 14.3 3.4l78.2 39.1 81.8 40.9c15.8 7.9 22.2 27.1 14.3 42.9l-64 128c-1.2 2.4-2.7 4.6-4.4 6.6zm-204.6-39.5 85.9 42.9L90.9 485.5C79 509.2 50.2 518.8 26.5 507s-33.3-40.8-21.4-64.5l102.5-205.1z"/></svg></a>');
            $brandDiv->appendChild($brandHtml);
            $body->appendChild($brandDiv);
        }
    }

    /** Adds debug info bar in debug mode */
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

    /** Removes class names from element */
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

    /** Converts relative URLs to absolute */
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
                    if (
                        strpos($href, 'mailto:') === 0 ||
                        strpos($href, 'tel:') === 0 ||
                        strpos($href, 'javascript:') === 0 ||
                        strpos($href, '#') === 0
                    ) {
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
