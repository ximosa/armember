<?php
if(file_exists('../../../wp-load.php'))
{
   require_once( '../../../wp-load.php' );
}

include_once __DIR__ . '/vendor/autoload.php';
use Mautic\Auth\ApiAuth;
use Mautic\MauticApi;

$arm_mautic_settings_ser = get_option('arm_mautic_settings');
$arm_mautic_settings = ($arm_mautic_settings_ser != '') ? maybe_unserialize($arm_mautic_settings_ser) : array();

if(!empty($arm_mautic_settings))
{
    /* 
    $baseUrl = 'https://reputeinfo.mautic.net'; 
    $version = 'OAuth2';
    $publicKey ='15_1qxlq1in6fdwwwcc44sg4ogwgwcc8ssg4kgss80goksw84ccgo';
    $secretKey = 't6mc9tb66dwokocss4w4gw08ccs8s48gckwwg8scoggg0wsos';
    $callback = 'http://www.reputeinfosystems.net/wordpress31/wp-content/plugins/armembermautic/auth_mautic.php'; */
      
    $baseUrl =  ($arm_mautic_settings['base_url'] != '') ? $arm_mautic_settings['base_url'] : '';
    $publicKey = ($arm_mautic_settings['public_key'] != '') ? $arm_mautic_settings['public_key'] : '';
    $secretKey =  ($arm_mautic_settings['secret_key'] != '') ? $arm_mautic_settings['secret_key'] : '';
    $version = 'OAuth2';
    $callback = ARM_MAUTIC_URL . '/auth_mautic.php';

    if($baseUrl != '' && $publicKey != '' && $secretKey != '')
    {
        // ApiAuth::initiate will accept an array of OAuth settings
        $settings = array(
                'baseUrl'      => $baseUrl, // Base URL of the Mautic instance
                'version'      => $version, // Version of the OAuth can be OAuth2 or OAuth1a. OAuth2 is the default value.
                'clientKey'    => $publicKey, // Client/Consumer key from Mautic
                'clientSecret' => $secretKey, // Client/Consumer secret key from Mautic
                'callback'     => $callback   // Redirect URI/Callback URI for this script
        );

        $auth = ApiAuth::initiate( $settings );

        $accessTokenData = get_option('arm_mautic_access_token_data');
        if ( isset( $accessTokenData ) && ! empty( $accessTokenData ) ) {
            $auth->setAccessTokenDetails( json_decode( $accessTokenData, true ) );
        }           

        if ( $auth->validateAccessToken() ) {

            $accessTokenData = $auth->getAccessTokenData();
            update_option( 'arm_mautic_access_token_data', json_encode( $accessTokenData ) );

            $auth->accessTokenUpdated();

            //do_action("arm_get_mautic_segment_list", $auth, $baseUrl, $publicKey, $secretKey);

            $segmentApi = MauticApi::getContext( "segments", $auth, $baseUrl . '/api/' );
            $segments = $segmentApi->getList();
            $segment_list = $segments['lists'];
            $data = array();
            if ( count( $segment_list ) > 0 ) {
                foreach ( $segment_list as $segment ) {
                        $segment_id = $segment['id'];
                        $data[] = array(
                                    'id'    => $segment_id,
                                    'name'  => $segment['name'],
                                    'alias' => $segment['alias'],
                                    );
                }
            } 

            $lists = $data;

            if (count($lists) > 0) {
                
                $arm_muatic_settings_array = array( 'base_url' => $baseUrl,
                                              'public_key'=> $publicKey,
                                              'secret_key'=> $secretKey,
                                              'status' => 1,
                                              'lists' =>  $lists,
                                              'default_list' => $lists[0]['id']
                                              );

                $arm_muatic_settings = maybe_serialize($arm_muatic_settings_array);
                update_option('arm_mautic_settings', $arm_muatic_settings);

            }    
            echo "<script type='text/javascript' id='mautic'>";
            echo "window.opener.document.getElementById('arm_mautic_varify').style = 'display:inline;';";
            echo "window.opener.document.getElementById('arm_mautic_link').style = 'display:none;';";
            echo "window.opener.document.getElementById('arm_mautic_error').style = 'display:none;';";
            echo "window.opener.document.getElementById('arm_mautic_action_link').style = 'display:block';";
            echo "window.opener.document.getElementById('arm_mautic_dl').className = 'arm_selectbox column_level_dd';";
            echo "window.opener.document.getElementById('arm_mautic_list').style = 'display:block';";
            echo "window.opener.document.getElementById('arm_mautic_status').value = '1';";
            echo "window.close();";
            echo "</script>";
        }
        else {
           echo "<script type='text/javascript' id='mautic'>";
           echo "window.opener.document.getElementById('arm_mautic_error').style = 'display:inline-block;';";
           echo "window.opener.document.getElementById('arm_mautic_status').value = '0';";
           echo "</script>";
        }
    }
    else {
        echo "<script type='text/javascript' id='mautic'>";
        echo "window.opener.document.getElementById('arm_mautic_error').style = 'display:inline-block;';";
        echo "window.opener.document.getElementById('arm_mautic_status').value = '0';";
        echo "</script>";
    }
}
else {
    echo "<script type='text/javascript' id='mautic'>";
    echo "window.opener.document.getElementById('arm_mautic_error').style = 'display:inline-block;';";
    echo "window.opener.document.getElementById('arm_mautic_status').value = '0';";
    echo "</script>";
}

  
?>