<?php
/*
 * ZenMagick - Smart e-commerce
 * Copyright (C) 2006-2010 zenmagick.org
 *
 * Portions Copyright (c) 2003 The zen-cart developers
 * Portions Copyright (c) 2003 osCommerce
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


/**
 * SQL/database utils.
 *
 * @author DerManoMann
 * @package zenmagick.store.shared.utils
 */
class ZMDbUtils {

    /**
     * Execute a SQL patch.
     *
     * @param string sql The sql.
     * @param array Result message list.
     * @param boolean Debug flag.
     * @return boolean <code>true</code> for success, <code>false</code> if the execution fails.
     */
    public static function executePatch($sql, $messages, $debug=false) {
        if ($debug) {
            $_GET['debug'] = 'ON';
        }
        // disable to allow plugins to insert HTML into the database...
        //$sql = ZMSecurityTools::sanitize($sql);
        if (!empty($sql)) {
            $results = ZMSQLRunner::zm_zen_execute_sql($sql, DB_DATABASE, DB_PREFIX);
            foreach (ZMDbUtils::processPatchResults($results) as $msg) {
                $messages[] = $msg;
            }
            return empty($results['error']);
        }

        return true;
    }

    /**
     * Resolve a given SQL file name.
     *
     * <p>This will try to find the most specific available file for the configured database type.</p>
     *
     * <p>Filenames are expected in the format <em>[name]-[driver].sql</em> if driver specific SQL is required. If no
     * specific file is found, the default <em>[name].sql</em> will is tried. If that is also not found, <code>null</code>
     * will be returned.</p>
     *
     * @param string filename The filename.
     * @return string The most specific filename or <code>null</code>.
     */
    public static function resolveSQLFilename($filename) {
        $config = ZMRuntime::getDatabase()->getConfig();
        $driver = $config['driver'];
        if (false !== ($ldot = strrpos($filename, '.'))) {
            $driverFilename = substr($filename, 0, $ldot) . '-' . $driver . substr($filename, $ldot);
            if (file_exists($driverFilename)) {
                return $driverFilename;
            }
        }

        if (file_exists($filename)) {
            return $filename;
        }

        return null;
    }

    /**
     * Process SQL patch messages.
     *
     * @param array The execution results.
     * @return array The results converted to messages.
     */
    private static function processPatchResults($results) {
        $messages = array();
        if ($results['queries'] > 0 && $results['queries'] != $results['ignored']) {
            array_push($messages, ZMLoader::make("ZMMessage", $results['queries'].' statements processed.', 'success'));
        } else {
            array_push($messages, ZMLoader::make("ZMMessage", 'Failed: '.$results['queries'].'.', 'error'));
        }

        if (!empty($results['errors'])) {
            foreach ($results['errors'] as $value) {
                array_push($messages, ZMLoader::make("ZMMessage", 'ERROR: '.$value.'.', 'error'));
            }
        }
        if ($results['ignored'] != 0) {
            array_push($messages, ZMLoader::make("ZMMessage", 'Note: '.$results['ignored'].' statements ignored. See "upgrade_exceptions" table for additional details.', 'warn'));
        }

        return $messages;
    }

}
