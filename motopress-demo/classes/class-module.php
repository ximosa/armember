<?php

namespace motopress_demo\classes;

use Motopress_Demo;

class Module extends Core {

	/**
	 * Install modules
	 */
	public static function install() {
		// include all core modules
		Core::include_all(Motopress_Demo::get_plugin_part_path('classes/modules/'));
	}

}
