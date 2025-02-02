<?php
/*
 * ZenMagick - Another PHP framework.
 * Copyright (C) 2011-2012 zenmagick.org
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA  02110-1301, USA.
 */
namespace zenmagick\http\view;

use zenmagick\base\Runtime;
use zenmagick\base\ZMObject;
use zenmagick\base\logging\Logging;


/**
 * Resource manager.
 *
 * <p>Handles resolving and injecting resources into the final output.</p>
 *
 * @author DerManoMann <mano@zenmagick.org>
 */
class ResourceManager extends ZMObject {
    const HEADER = 'header';
    const FOOTER = 'footer';
    const NOW = 'now';
    private $resources_;
    private $resourcesAsTemplates_;
    private $view;
    private $fragments;


    /**
     * Create new instance.
     */
    public function __construct() {
        parent::__construct();
        $this->resources_ = array('css' => array(), 'js' => array());
        $this->resourcesAsTemplates_ = false;
        $this->view = null;
        $this->fragments = array();
    }


    /**
     * Set the view.
     *
     * <p>The view this instance is associated with.</p>
     *
     * @param View view The view.
     */
    public function setView(View $view) {
        $this->view = $view;
    }

    /**
     * Get the view.
     *
     * @return View The view.
     */
    public function getView() {
        return $this->view;
    }

    /**
     * Set the '<em>resources as templates</em>' flag.
     *
     * <p>If set, resources will be looked up in the same location as templates.</p>
     *
     * @param boolean value The new value.
     */
    public function setResourcesAsTemplates($value) {
        $this->resourcesAsTemplates_ = $value;
    }

    /**
     * Check if the <em>resources as templates</em>' flag is set.
     *
     * @return boolean <code>true</code> if, and only if, resources are looked up as templates.
     */
    public function isResourcesAsTemplates() {
        return $this->resourcesAsTemplates_;
    }

    /**
     * Add inline CSS.
     *
     * @param string css The CSS.
     * @param array attr Optional attribute map; special keys 'prefix' and 'suffix' may be used to wrap.
     */
    public function css($css, $attr=array()) {
        $this->styles($css, true, $attr);
    }

    /**
     * Add link to the given CSS file or create inline CSS in the head element of the response.
     *
     * @param string filename A relative CSS filename.
     * @param array attr Optional attribute map; special keys 'prefix' and 'suffix' may be used to wrap.
     */
    public function cssFile($filename, $attr=array()) {
        $this->styles($filename, false, $attr);
    }

    /**
     * Add link to the given CSS file or create inline CSS in the head element of the response.
     *
     * @param string css A relative filename or plain CSS.
     * @param boolean inline A relative CSS filename.
     * @param array attr Optional attribute map; special keys 'prefix' and 'suffix' may be used to wrap.
     */
    protected function styles($css, $inline=false, $attr=array()) {
        if (!array_key_exists($css, $this->resources_['css'])) {
            // avoid duplicates
            $this->resources_['css'][$css] = array(
                'filename' => $css,
                'inline' => $inline,
                'external' => $this->isExternal($css),
                'attr' => $attr
            );
        }
    }

    /**
     * Add the given JavaScript file to the final contents or create script reference (default).
     *
     * @param string filename A relative JavaScript filename.
     * @param boolean inline Optional flag that can be used to control whether to create a link
     *  or insert JavaScript inline; default is <code>false</code> to link.
     * @param string position Optional position; either <code>HEADER</code> (default), <code>FOOTER</code> or <code>NOW</code>.
     */
    public function jsFile($filename, $position=self::HEADER, $inline=false) {
        if (array_key_exists($filename, $this->resources_['js'])) {
            // check if we need to do anything else or update the position
            if ($this->resources_['js'][$filename]['done']) {
                Runtime::getLogging()->log('skipping '.$filename.' as already done', Logging::TRACE);
                return;
            }
            if (self::FOOTER == $this->resources_['js'][$filename]['position']) {
                if (self::HEADER == $position) {
                    Runtime::getLogging()->log('upgrading '.$filename.' to HEADER', Logging::TRACE);
                    return;
                }
            }
            // either it's now or same as already registered
        }

        // record details in any case
        $this->resources_['js'][$filename] = array(
            'filename' => $filename,
            'inline' => $inline,
            'position' => $position,
            'external' => $this->isExternal($filename),
            'done' => false
        );

        if (self::NOW == $position) {
            $this->resources_['js'][$filename]['done'] = true;
            if ($this->resources_['js'][$filename]['inline']) {
                echo '<script type="text/javascript">',"\n";
                $this->view->fetch($filename);
                echo '</script>',"\n";
            } else {
                // avoid empty src
                if (null != ($src = $this->resolveResource($filename)) && !empty($src)) {
                    echo '<script type="text/javascript" src="',$src,'"></script>',"\n";
                }
            }
        }
    }

    /**
     * Resolve resource path as url.
     *
     * @param string resource The (relative) path to the resource.
     * @return string The resolved final URL.
     */
    public function resolveResource($resource) {
        if ($this->isExternal($resource)) {
            return $resource;
        }

        if ('/' == $resource[0]) {
            // absolute path
            return $resource;
        } else {
            $type = $this->resourcesAsTemplates_ ? View::TEMPLATE : View::RESOURCE;
            if (null != ($path = $this->view->getResourceResolver()->findResource($resource, $type))) {
                if (null != ($uri= $this->file2uri($path))) {
                    $url = $this->view->getRequest()->absoluteUrl($uri);
                    Runtime::getLogging()->log(sprintf('resolved file "%s" as url: %s; path=%s', $resource, $url, $path), Logging::TRACE);
                    return $url;
                }
            }

            return '';
        }
    }

    /**
     * Convert a full fs path to uri.
     *
     * @param string filename The full filename.
     * @return string The uri or <code>null</code> if the filename is invalid.
     */
    public function file2uri($filename) {
        $filename = realpath($filename);
        $docRoot = realpath($this->view->getRequest()->getDocRoot());
        if (empty($filename) || empty($docRoot)) {
            Runtime::getLogging()->warn(sprintf('cannot convert t"%s" to url; docroot: %s', $filename, $docRoot));
            return null;
        }
        if (0 !== strpos($filename, $docRoot)) {
            // outside docroot
            Runtime::getLogging()->warn(sprintf('cannot convert t"%s" to url (basedir); docroot: %s', $filename, $docRoot));
            return null;
        }

        return str_replace(DIRECTORY_SEPARATOR, '/', substr($filename, strlen($docRoot)));
    }

    /**
     * Check if a given resource is external.
     *
     * @param string resource The resource.
     * @return boolean <code>true</code> if the resource is external.
     */
    public function isExternal($resource) {
        return 0 === strpos($resource, '//') || false !==  strpos($resource, '://');
    }

    /**
     * Handle all resources of a given group and location.
     *
     * @param array Resource details.
     * @param string group The group; either <code>css</code> or <code>js</code>.
     * @param string location The location; either <code>ResourceManager::HEADER</code> or <code>ResourceManager::FOOTER</code>.
     * @return string The final content ready to be injected into the final contents.
     */
    public function handleResourceGroup($files, $group, $location) {
        $contents = '';

        if ('js' == $group) {
            foreach ($files as $details) {
                // avoid empty src
                if (null != ($src = $this->resolveResource($details['filename'])) && !empty($src)) {
                    $contents .= '<script type="text/javascript" src="'.$src.'"></script>'."\n";
                }
            }
        } else if ('css' == $group) {
            $slash = $this->container->get('settingsService')->get('zenmagick.http.html.xhtml') ? '/' : '';
            $css = '';
            foreach ($files as $details) {
                $load = null;
                $details['attr'] = array_merge(array('rel' => 'stylesheet', 'type' => 'text/css', 'prefix' => '', 'suffix' => ''), $details['attr']);
                // merge in defaults
                $attr = '';
                foreach ($details['attr'] as $name => $value) {
                    if (null !== $value && !in_array($name, array('prefix', 'suffix'))) {
                        $attr .= ' '.$name.'="'.$value.'"';
                    }
                }
                if ($details['inline']) {
                    $load = sprintf('<style%s>%s</style>', $attr, $details['filename']);
                } else if (null != ($href = $this->resolveResource($details['filename'])) && !empty($href)) {
                    $load = sprintf('<link%s href="%s"%s>', $attr, $href, $slash);
                }
                if ($load) {
                    $css .= $details['attr']['prefix'];
                    $css .= $load;
                    $css .= $details['attr']['suffix']."\n";
                }
            }
            $contents .= $css;
        }

        return $contents;
    }

    /**
     * Process all resources.
     *
     * @return array Final contents for <em>header</em> and <em>footer</em> or <code>null</code>.
     */
    public function getResourceContents() {
        if (0 == count($this->resources_['js']) && 0 == count($this->resources_['css'])) {
            return null;
        }

        // first build separate lists to allow group processing
        $header = array();
        $footer = array();
        foreach ($this->resources_['js'] as $filename => $details) {
            if (!$details['done']) {
                if (self::HEADER == $details['position']) {
                    $header[] = $details;
                } else if (self::FOOTER == $details['position']) {
                    $footer[] = $details;
                }
                $this->resources_['js'][$filename]['done'] = true;
            }
        }
        if (0 == count($header) && 0 == count($footer) && 0 == count($this->resources_['css'])) {
            return null;
        }

        // process
        $contents = array('header' => '', 'footer' => '');

        $contents['header'] .= $this->handleResourceGroup($this->resources_['css'], 'css', self::HEADER);

        $contents['header'] .= $this->handleResourceGroup($header, 'js', self::HEADER);
        $contents['footer'] .= $this->handleResourceGroup($footer, 'js', self::FOOTER);

        return $contents;
    }

    /**
     * Fragment handling - either set a fragment marker or populate a fragment.
     *
     * @param string name The fragment name.
     * @param string value The fragment value; default is <code>null</code> to set a marker.
     * @return string The fragment marker content or an empty string on a set.
     */
    public function fragment($name, $value=null) {
        $key = sprintf('<!--ZenMagick:fragment:%s-->', $name);
        $this->fragments[$key] = $value;
        return $key;
    }

    /**
     * Get the fragments map.
     *
     * @return array Map of fragment key =&gt; value pairs.
     */
    public function getFragments() {
        return $this->fragments;
    }

}
