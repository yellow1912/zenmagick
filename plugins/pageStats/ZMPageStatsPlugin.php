<?php
/*
 * ZenMagick - Smart e-commerce
 * Copyright (C) 2006-2010 zenmagick.org
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
?>
<?php

use zenmagick\base\Runtime;
use zenmagick\base\events\EventDispatcher;
use zenmagick\base\logging\Logging;

/**
 * Plugin to show page stats.
 *
 * @package org.zenmagick.plugins.pageStats
 * @author DerManoMann
 */
class ZMPageStatsPlugin extends Plugin {
    private $pageCache_;
    private $events_;


    /**
     * Create new instance.
     */
    function __construct() {
        parent::__construct('Page Stats', 'Show page stats', '${plugin.version}');
        $this->setLoaderPolicy(ZMPlugin::LP_NONE);
        $this->pageCache_ = null;
        $this->events_ = array();
    }

    /**
     * Destruct instance.
     */
    function __destruct() {
        parent::__destruct();
    }


    /**
     * {@inheritDoc}
     */
    public function install() {
        parent::install();

        $this->addConfigValue('Hidden Stats', 'hideStats', 'false', 'If set to true, page stats will be hidden (as HTML comment).',
            'widget@ZMBooleanFormWidget#name=hideStats&default=false&label=Hide stats');
        $this->addConfigValue('Events', 'showEvents', 'false', 'Enable to display all fired events.',
            'widget@ZMBooleanFormWidget#name=showEvents&default=false&label=Show events');
        $this->addConfigValue('SQL', 'showSQLtiming', 'false', 'Enable to display all executed SQL and related timings.',
            'widget@ZMBooleanFormWidget#name=showSQLtiming&default=false&label=Show SQL');
        $this->addConfigValue('Limit displayed SQL', 'sqlTimingLimit', '0', 'Limit displayed SQL to the top X queries (0 for all).');
        $this->addConfigValue('Dump Queries to Error Log', 'dumpQueries', 'false', 'If set to true, all SQL queries will be dumped to error log.',
            'widget@ZMBooleanFormWidget#name=dumpQueries&default=false&label=Dump queries to error log');
    }

    /**
     * {@inheritDoc}
     */
    public function init() {
        parent::init();
        // register to log events
        Runtime::getEventDispatcher()->listen(array($this, 'logEvent'));
        // register for method mapped events
        Runtime::getEventDispatcher()->listen($this);
    }

    /**
     * Log all events.
     *
     * @param Event event An event.
     * @param mixed value Optional value for filter events.
     */
    public function logEvent($event, $value=null) {
        Runtime::getLogging()->info('event:'.(null!=$event->getSubject()?' ('.get_class($event->getSubject()).')':'').': ' . $event->getName() . '/'.EventDispatcher::n2m($event->getName()));
        $this->events_[] = $event;
        return $value;
    }

    /**
     * Get the database dao.
     *
     * @return queryFactory *The* zen-cart <code>queryFactory</code> instance.
     */
    private function getDB() { global $db; return $db; }

    /**
     * Generate hidden stats.
     *
     * @param ZMRequest request The current request.
     * @param ZMView view The current view.
     */
    private function hiddenStats($request, $view) {
        ob_start();
        echo '<!--'."\n";
        echo '  Client IP: '.$_SERVER['REMOTE_ADDR']."\n";
        echo '  PHP: '.phpversion()."\n";
        echo '  ZenMagick: '.Runtime::getSettings()->get('zenmagick.version')."\n";
        echo '  environment: '.ZM_ENVIRONMENT."\n";
        echo '  total page execution: '.Runtime::getExecutionTime().' secconds;'."\n";
        if (null != ($db = $this->getDB())) {
            echo '  db: SQL queries: '.$db->queryCount().', duration: '.round($db->queryTime(), 4).' seconds;';
        }
        echo '  databases: ';
        foreach (ZMRuntime::getDatabases() as $database) {
            $config = $database->getConfig();
            $stats = $database->getStats();
            echo $config['database'].'('.get_class($database).'): SQL queries: '.$stats['queries'].', duration: '.round($stats['time'], 4).' seconds; ';
        }
        echo "\n";

        if (null != $view) {
            $vars = $view->getVars();
            if (isset($vars['exception']) && null !== ($exception = $vars['exception'])) {
                echo "\n".$exception."\n\n";
            }
        }

        echo '-->'."\n";
        if (ZMLangUtils::asBoolean($this->get('showEvents'))) {
            echo '<!--'."\n";
            echo '  '.Runtime::getExecutionTime(ZM_START_TIME).' ZM_START_TIME '."\n";
            foreach ($this->events_ as $event) {
                echo '  '.Runtime::getExecutionTime($event->getTimestamp()).' '.EventDispatcher::n2m($event->getName()).' / '.$event->getName().' args: '.implode(',', array_keys($event->all()))."\n";
            }
            echo '-->'."\n";
        }

        if (ZMLangUtils::asBoolean($this->get('showSQLtiming'))) {
            $limit = $this->get('sqlTimingLimit');
            echo '<!--'."\n";
            echo '  SQL timings: ';
            foreach (ZMRuntime::getDatabases() as $database) {
                $config = $database->getConfig();
                $stats = $database->getStats();
                $details = $stats['details'];
                usort($details, array($this, "compareStats"));
                if (0 != $limit && count($details) > $limit) {
                    $details = array_slice($details, 0, $limit);
                }
                echo $config['database'].'('.get_class($database).'):'."\n";
                foreach ($details as $query) {
                    echo $query['time'].': '.$query['sql']."\n";
                }
            }
            echo '-->'."\n";
        }

        if (ZMLangUtils::asBoolean($this->get('dumpQueries'))) {
            foreach (ZMRuntime::getDatabases() as $database) {
                $stats = $database->getStats();
                $details = $stats['details'];
                foreach ($details as $query) {
                    error_log("QUERYLOG: " . $query['start'] . " [" . $query['time'] . " secs] " . $query['sql']);
                }
            }
        }

        return ob_get_clean();
    }

    /**
     * Event handler for page cache hits.
     */
    public function onPluginsPageCacheContentsDone($event) {
        echo $this->hiddenStats($event->get('request'), null);
    }

    /**
     * Handle finalize contents filter event.
     */
    public function onFinaliseContents($event, $contents) {
        $request = $event->get('request');
        $view = $event->has('view') ? $event->get('view') : null;

        if (ZMLangUtils::asBoolean($this->get('hideStats'))) {
            return $contents.$this->hiddenStats($request, $view);
        }

        ob_start();
        $slash = ZMSettings::get('zenmagick.mvc.html.xhtml') ? '/' : '';
        echo '<div id="page-stats">';
        echo 'Client IP: <strong>'.$_SERVER['REMOTE_ADDR'].'</strong>;';
        echo '&nbsp;&nbsp;&nbsp;PHP: <strong>'.phpversion().'</strong>;';
        echo '&nbsp;&nbsp;&nbsp;ZenMagick: <strong>'.Runtime::getSettings()->get('zenmagick.version').'</strong>;';
        echo '&nbsp;&nbsp;&nbsp;environment: <strong>'.ZM_ENVIRONMENT.'</strong>;';
        echo '&nbsp;&nbsp;&nbsp;total page execution: <strong>'.Runtime::getExecutionTime().'</strong> secconds;<br'.$slash.'>';
        if (null != ($db = $this->getDB())) {
            echo '<strong>db</strong>: SQL queries: <strong>'.$db->queryCount().'</strong>, duration: <strong>'.round($db->queryTime(), 4).'</strong> seconds;';
        }
        echo '&nbsp;&nbsp;<strong>databases:</strong> ';
        foreach (ZMRuntime::getDatabases() as $database) {
            $config = $database->getConfig();
            $stats = $database->getStats();
            echo $config['database'].'('.get_class($database).'): SQL queries: <strong>'.$stats['queries'].'</strong>, duration: <strong>'.round($stats['time'], 4).'</strong> seconds; ';
        }
        echo '<br'.$slash.'>';
        $lstats = ZMLoader::instance()->getStats(true);
        echo 'ZMLoader: '.$lstats['static'].' static and '.$lstats['class'].' class files loaded [of '.count(get_included_files()).'], '.$lstats['instances'].' objects instantiated.<br'.$slash.'>';
        echo '</div>';
        if (ZMLangUtils::asBoolean($this->get('showEvents'))) {
            echo '<div id="event-log">';
            echo '<table border="1">';
            echo '<tr>';
            echo '<td style="text-align:right;padding:4px;">'.Runtime::getExecutionTime(ZM_START_TIME).'</td>';
            echo '<td colspan="4" style="text-align:left;padding:4px;">ZM_START_TIME</td>';
            echo '</tr>';
            foreach ($this->events_ as $event) {
                echo '<tr>';
                echo '<td style="text-align:right;padding:4px;">'.Runtime::getExecutionTime($event->getTimestamp()).'</td>';
                echo '<td style="text-align:left;padding:4px;">'.$event->getName().'</td>';
                echo '<td style="text-align:left;padding:4px;">'.sprintf("%d", $event->getMemory()).'</td>';
                echo '<td style="text-align:left;padding:4px;">'.EventDispatcher::n2m($event->getName()).'</td>';
                $eargs = $event->all();
                if ('finalise_contents' == $event->getName()) {
                    $eargs['contents'] = '**response**';
                }
                // handle array eargs
                foreach ($eargs as $key => $value) {
                    if (is_array($value)) {
                        $eargs[$key] = implode(',', $value);
                    }
                }
                $argsInfo = implode(',', $eargs);
                $argsInfo = empty($argsInfo) ? '&nbsp;' : $argsInfo;
                echo '<td style="text-align:left;padding:4px;">'.$argsInfo.'</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';
        }

        if (ZMLangUtils::asBoolean($this->get('showSQLtiming'))) {
            $limit = $this->get('sqlTimingLimit');
            echo '<div id="sql-timings">';
            echo '<table border="1">';
            echo '<tr><th>Time (sec)</th><th>SQL</td></tr>';
            foreach (ZMRuntime::getDatabases() as $database) {
                $stats = $database->getStats();
                $details = $stats['details'];
                usort($details, array($this, "compareStats"));
                if (0 != $limit && count($details) > $limit) {
                    $details = array_slice($details, 0, $limit);
                }
                echo '<tr><th colspan="2">'.$config['database'].'('.get_class($database).')</th></tr>'."\n";
                foreach ($details as $query) {
                    echo '<tr><td>'.$query['time'].'</td><td>'.$query['sql']."</td></tr>";
                }
            }
            echo '</table>';
        }

        if (null != $view) {
            $vars = $view->getVars();
            if (isset($vars['exception']) && null !== ($exception = $vars['exception'])) {
                echo '<pre>';
                echo $exception;
                echo '</pre>';
            }
        }

        $info = ob_get_clean();

        return preg_replace('/<\/body>/', $info . '</body>', $contents, 1);
    }

    /**
     * Compare sql stats.
     */
    protected function compareStats($a, $b) {
        $an = $a['time'];
        $bn = $b['time'];
        if ($an == $bn) {
            return 0;
        }
        return ($an < $bn) ? 1 : -1;
    }

}
