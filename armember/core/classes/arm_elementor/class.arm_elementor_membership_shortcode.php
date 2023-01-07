<?php 
class arm_membership_elementcontroller{
  
   function __construct() {
        add_action( 'plugins_loaded', array( $this, 'arm_element_widget' ) );
   } 
   function arm_element_widget(){
      if ( ! did_action( 'elementor/loaded' ) ) {
         return;
      }
      
      require_once(MEMBERSHIP_WIDGET_DIR . '/arm_elm_widgets/class.arm_elementor_widget_element.php');
   }
}
?>