<?php

namespace motopress_demo\classes\controllers;

use motopress_demo\classes\Controller;
use motopress_demo\classes\models\Sandbox;
use motopress_demo\classes\Shortcodes;

class Controller_Sandbox extends Controller
{

    protected static $instance;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Action sand Creation Form E-mail
     */
    public function action_reset() {
        // Check security
        $reset_successfully = false;

        if (isset($_POST['security']) && wp_verify_nonce($_POST['security'], 'mp-ajax-nonce')) {
            $reset_successfully = Sandbox::get_instance()->reset();
        }

        if ($reset_successfully) {
            wp_send_json_success(array('status' => true));
        } else {
            wp_send_json_error(array('data' => _('This is not sandbox', 'mp-demo'), 'status' => false), 501);
        }
    }

    public function action_add_row() {
        wp_send_json_success(mp_demo_render_replace_table_row(array(), false));
    }

    public function action_export_uploads() {
        $data = array();
        $template_data = array(
            'export_uploads' => false,
            'count' => 0
        );

        $options = $_POST['options'];
        $blog_export_folder = $_POST['blog_export_folder'] . '/uploads';

        if (!empty($options['blog_upload_folder'])) {
            $template_data['export_uploads'] = true;
            $template_data['count'] = Sandbox::get_instance()->recursive_file_copy($options['blog_upload_folder'], $blog_export_folder, 0);
        }

        $data['html'] = mp_demo_export_uploads_output($template_data);

        wp_send_json_success($data);
    }

    public function action_export_tables() {
        $data = array();
        $template_data = array(
            'export_tables' => false,
            'tables_count' => 0,
        );

        $options = $_POST['options'];
        $folders = $this->get_dump_folders();
        $file = $folders['export'] . '/db.sql';
        $mysqldump_error = $folders['dist'] . '/mysqldump_error.log';
        $data['tables_count'] = 0;
        $data['blog_export_folder'] = $folders['export'];

        if (isset($options['tables']) && (count($options['tables']) > 0)) {
            $template_data['export_tables'] = true;
            $template_data['tables'] = $options['tables'];
            $data['tables_count'] = count($options['tables']);
            $template_data['details'] = $this->get_sqldump($file, implode(' ', $options['tables']), $mysqldump_error);
        }

        $data['sql_file'] = $file;
        $data['html'] = mp_demo_export_tables_output($template_data);



        wp_send_json_success($data);
    }


    public function action_apply_replacements() {
        $data = array();
        $options = $_POST['options'];
        $file = $_POST['sql_file'];
        $tables_count = $_POST['tables_count'];

        if ($tables_count > 0) {
            $data['action'] = 'applay_replacements';

            $finds = (isset($options['find']) && is_array($options['find'])) ? $options['find'] : array();
            $replace = (isset($options['replace']) && is_array($options['replace'])) ? $options['replace'] : array();

            $html = '<p>'
                . '<b>' . __('Applied replacements:', 'mp-demo') . '</b><ol>';

            // Remove custom variables
            $result = $this->replace_in_file($file, $finds, $replace);
            foreach ($finds as $key => $find) {
                $html .= '<li> ' . $find . ' => ' . $replace[$key] . '</li>';
                $html .= ($result['status'] == 'error') ? ' (' . $result['message'] . ')' : '';
            }

            // Remove utility variables
            $mp_variables_pattern = '/\(\d+,\'((widget\_mp\_demo\_\w+)|(mp\_demo\_\w+)|(mp\_user))\',\'[\w\s\\\&"\';\.:?\!\-\+\*\=%\@\{\}]*\',\'(yes|no)\'\),/';
            $result = $this->replace_in_file($file, $mp_variables_pattern, '', true);

            $message = ($result['status'] == 'error') ? __('%s utilities removal failed: %s', 'mp-demo') : __('%s utilities were successfully removed', 'mp-demo');

            $html .= '<li>';
            $html .= sprintf($message, \Motopress_Demo::get_instance()->get_plugin_full_name(), $result['message']);
            $html .= '</li>';
            $html .= '</ol></p>';

            $data['html'] = $html;
        }

        wp_send_json_success($data);
    }

    public function action_create_zip() {
        $blog_export_folder = str_replace( '\\', '/', $_POST['blog_export_folder']);
        $blog_export_folder = preg_replace('#/+#','/',$blog_export_folder);
        $blog_export_zip = $blog_export_folder . '.zip';
        $dist_folder = get_home_path();
        $dist_folder = str_replace( '\\', '/', $dist_folder);

        $output = $this->get_zip($blog_export_folder, $blog_export_zip);

        $data = array();

        $template_data = array(
            'status' => $output,
            'zip_url' => get_bloginfo('url') .'/' . str_replace($dist_folder, '', $blog_export_zip),
            'zip_path' => $blog_export_zip
        );

        $data['html'] = mp_demo_create_zip_output($template_data);

        wp_send_json_success($data);
    }

    public function action_remove_export() {

        $dir = $_POST['source_path'];
        $data = array();

        $template_data = array(
            'remove_zip' => $dir,
            'remove_folder' => str_replace('.zip', '', $dir),
        );

        if (!file_exists($template_data['remove_zip'])) {
            $data['html'] = '<p>' . __('File does not exist.', 'mp-demo') . '</p>';

            wp_send_json_success($data);
        }

        $data['html'] = mp_demo_remove_export_output($template_data);

        // unlink ZIP
        unlink($template_data['remove_zip']);

        // delete folder
        $it = new \RecursiveDirectoryIterator($template_data['remove_folder'], \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);

        foreach($files as $file) {
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($template_data['remove_folder']);

        wp_send_json_success($data);
    }

    function get_dump_folders() {
        $options = $_POST['options'];
        $export_folder = $options['dist_folder'] . '/dump-' . $options['blog_id'] . '-' . date('Y-m-d-H-i-s');

        if (!file_exists($options['dist_folder'])) {
            mkdir($options['dist_folder'], 0777, true);
        }

        if (!file_exists($export_folder)) {
            mkdir($export_folder, 0777, true);
        }

        return array(
            'dist' => $options['dist_folder'],
            'export' => $export_folder
        );
    }


    function get_sqldump($file, $tables, $error_file = false) {

        if (is_array($tables)) {
            $tables = implode(' ', $tables);
        }

        $host = explode(':', DB_HOST);
        $host = reset($host);
        $port = strpos(DB_HOST, ':') ? end(explode(':', DB_HOST)) : '';
        $sqldump_path = $this->get_sqldump_path();
        //Build command
        $cmd = $sqldump_path;
        $cmd .= ' --no-create-db --single-transaction --quick';
        $cmd .= ' -u ' . escapeshellarg(DB_USER);
        $cmd .= (DB_PASSWORD) ? ' -p' . escapeshellarg(DB_PASSWORD) : '';
        $cmd .= ' -h ' . escapeshellarg($host);
        $cmd .= (!empty($port) && is_numeric($port)) ? ' -P ' . $port : '';
        $cmd .= ' -r ' . escapeshellarg($file);
        $cmd .= ($error_file) ? ' --log-error=' . escapeshellarg($error_file) : '';
        $cmd .= ' ' . escapeshellarg(DB_NAME);
        $cmd .= ' ' . $tables;
        $cmd .= ' 2>&1';

        $output = shell_exec($cmd);

        return $output;
    }


    function get_zip($source, $destination) {
        if (!extension_loaded('zip') || !file_exists($source)) {
            return false;
        }

        $zip = new \ZipArchive();

        if (!$zip->open($destination, \ZIPARCHIVE::CREATE)) {
            return false;
        }

        $source = str_replace('\\', '/', realpath($source));

        if (is_dir($source) === true) {

            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source), \RecursiveIteratorIterator::SELF_FIRST);

            foreach ($files as $file) {
                $file = str_replace('\\', '/', $file);

                // Ignore "." and ".." folders
                if (in_array(substr($file, strrpos($file, '/') + 1), array('.', '..')))
                    continue;

                $file = str_replace('\\', '/', realpath($file));

                if (is_dir($file) === true) {
                    $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                } else if (is_file($file) === true) {
                    $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                }
            }
        } else if (is_file($source) === true) {
            $zip->addFromString(basename($source), file_get_contents($source));
        }

        return $zip->close();
    }


    public function get_sqldump_path() {
        //Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

            $path1 = '';
            $mysqldump = $this->sanitize_shell_path(`where mysqldump`);
            if (@is_executable($mysqldump))
                $path1 = (!empty($mysqldump)) ? $mysqldump : '';

            $paths = array(
                $path1,
                'C:/xampp/mysql/bin/mysqldump.exe',
                'C:/Program Files/xampp/mysql/bin/mysqldump',
                'C:/Program Files/MySQL/MySQL Server 6.0/bin/mysqldump',
                'C:/Program Files/MySQL/MySQL Server 5.5/bin/mysqldump',
                'C:/Program Files/MySQL/MySQL Server 5.4/bin/mysqldump',
                'C:/Program Files/MySQL/MySQL Server 5.1/bin/mysqldump',
                'C:/Program Files/MySQL/MySQL Server 5.0/bin/mysqldump',
            );

            //Linux
        } else {
            $path1 = '';
            $path2 = '';
            $mysqldump = $this->sanitize_shell_path(`which mysqldump`);
            if (@is_executable($mysqldump))
                $path1 = (!empty($mysqldump)) ? $mysqldump : '';

            $paths = array(
                $path1,
                $path2,
                '/usr/local/bin/mysqldump',
                '/usr/local/mysql/bin/mysqldump',
                '/usr/mysql/bin/mysqldump',
                '/usr/bin/mysqldump',
                '/opt/local/lib/mysql6/bin/mysqldump',
                '/opt/local/lib/mysql5/bin/mysqldump',
                '/opt/local/lib/mysql4/bin/mysqldump',
            );
        }

        // Find the one which works
        foreach ($paths as $path) {
            if (@is_executable($path))
                return $path;
        }

        return false;
    }

    /**
     * Replaces a string in a file
     *
     * @param string $file_path
     * @param string $search
     * @param string $replace
     * @return array $result
     */
    function replace_in_file($file_path, $search, $replace, $use_regex = false) {
        $result = array('status' => 'error', 'message' => '');

        if (file_exists($file_path) === true) {

            if (is_writeable($file_path)) {

                try {
                    $file_content = file_get_contents($file_path);

                    if ($use_regex) {
                        $file_content = preg_replace($search, $replace, $file_content);
                    } else {
                        $file_content = str_replace($search, $replace, $file_content);
                    }

                    if (file_put_contents($file_path, $file_content) > 0) {
                        $result['status'] = 'success';
                    } else {
                        $result['message'] = __('Error while writing file ' . $file_path . '.', 'mp-demo');
                    }

                } catch (Exception $e) {
                    $result['message'] = sprintf(__('Error: %s.', 'mp-demo'), $e);
                }

            } else {
                $result['message'] = sprintf(__('File %s is not writable.', 'mp-demo'), $file_path);
            }

        } else {
            $result['message'] = sprintf(__('File %s does not exist.', 'mp-demo'), $file_path) ;
        }

        return $result;
    }

    function sanitize_shell_path($path)
    {

        return str_replace(array("\n", "\r"), '', $path);
    }
}
