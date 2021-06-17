<?php
namespace KPM;

require_once 'model.php';
require_once KPM_DIR.'components/table-request-helper.php';

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( '\KPM\CategoryModel' )):

    /**
     * Keeki Category Model,
     * Interface with API class and retrieve WEBGROUPS to store as WP categories
     *
     *
     * @class Category
     * @version	0.1
     * @since 0.1
     * @package	KPM
     * @author Conduct
     */
    class CategoryModel extends Model {

        /**
         * Store the entire list of available groups for usage in product insertion
         * @var array
         */
        public $webgroup_ids = array();

        public $webgroup_categories = array();

        /**
         * Internal WC Taxonomy for product categories
         *
         */
        private $_category_taxonomy = 'product_cat';

        /**
         * Use the API to get product data
         * Where appropriate update the db product data
         *
         * @param Bool $save whether to save updated product data
         */
        public function updateCategories() {
            
            //Remove all product categories first
            $this->cleanCategoryList();

            //Build the categories

            $children_webgroup_data = array();
            list($table,$fields,$conditions,$sortBy,$limit,$offset) = $this->_webgroupsParams();
            $field_batch = array(
                'field' =>  $sortBy,
                'value' => '000'
            );            
            $count = 0;
            do{
                $children_webgroup_data_temp = RequestHelper::batchRequest($table,$fields,$conditions,$sortBy,true, $offset, $count, $field_batch);
                $children_webgroup_data = \array_merge($children_webgroup_data, $children_webgroup_data_temp);
                $count++;
            } while(count($children_webgroup_data_temp[$table]) >= API_RECORD_LIMIT);
            unset ($children_webgroup_data_temp);

            $parent_webgroup_data = array();
            list($table,$fields,$conditions,$sortBy,$limit,$offset) = $this->_parentwebgroupsParams();
            $count = 0;
            do {
                $parent_webgroup_data_temp = RequestHelper::batchRequest($table,$fields,$conditions,$sortBy,false, $offset, $count);
                $parent_webgroup_data = \array_merge($parent_webgroup_data, $parent_webgroup_data_temp);
                $count++;
            } while(count($parent_webgroup_data_temp[$table]) >= API_RECORD_LIMIT);
            unset($parent_webgroup_data_temp);

            $webgroup_data[$table] = \array_merge($parent_webgroup_data[$table], $children_webgroup_data[$table]);

            if(!empty($webgroup_data[$table])) {
                $this->webgroup_categories = $webgroup_data[$table];
                //Ensure array order is root-cat->sub-cat->sub-sub-cat etc, so parent isn't added after child
                $this->_asortWebgroupData($this->webgroup_categories);

                foreach($this->webgroup_categories as $category) {
                   $this->_addCategoryData($category);
                }
                if(!empty($this->webgroup_ids)) {
                    clean_term_cache($this->webgroup_ids);
                }
            }
            
            return $this->webgroup_ids;
        }

        /**
         * Delete all products categories before rebuild.
         * @param none
         */
        private function cleanCategoryList() {
            $args = array(
                'type' => 'post',
                'taxonomy' => 'product_cat',
                'hide_empty' => 0
            );

            $categories = get_categories($args);

            foreach ($categories as $category) {
                wp_delete_term( $category->term_id, 'product_cat');
            }

        }

        /**
         * After products have been saved clean up unused categories and add menu items
         * @param $webgroup_ids
         */
        public function postProductUpdateCategories($used_webgroup_ids)  {
            //remove unused categories
            error_log('Removing unsused categories...');
            $save_array = array();
            foreach($this->webgroup_categories as $cat) {
                if(isset($used_webgroup_ids[$cat['WEBGROUP']])) {
                    $save_array = array_merge($save_array,$this->_catArr($cat));
                }
            }
            foreach($this->webgroup_ids as $checkid) {
                if(!in_array($checkid,$save_array)) {
		            wp_delete_term($checkid,'product_cat');
		        }
            }
            if(!empty($this->webgroup_ids)) {
                clean_term_cache($this->webgroup_ids);
            }
            //Add the menu
            error_log('Updating menu...');
            $this->updateMenu($save_array);
        }

        /**
         * Recursive method to store a webgroup category and recursively store its parents
         *
         * @param String $current_parent the current parent webgroup name (empty for root).
         * @param Array $webgroups the original source data array as from the API.
         * @param Array $stacked_arr the new sorted array, stacked with ordered group results
         */
        private function _catArr($category) {
            $stacked_arr = array();
            //Something bad has happened
            if(!isset($this->webgroup_ids[$category['WEBGROUP']])) { return array(); }

            $stacked_arr[] = $this->webgroup_ids[$category['WEBGROUP']];
            foreach($this->webgroup_categories as $existing_category) {
		        if($existing_category['WEBGROUP'] == $category['PARGROUP1']) {
                    $parent = $existing_category;
                    $stacked_arr = array_merge($stacked_arr,$this->_catArr($parent));
		            break;
		        }
            }
	        return $stacked_arr;
        }

        /**
         * Check which categories have been used by products and save as menu items
         * @param $webgroup_ids
         */
        public function updateMenu($saved_categories) {
            //All at once update the nav menu (deleted terms will be removed from menu even after re-adding)
            if(empty($this->webgroup_categories)) {
                error_log('Menu called before updateCategories, or categories are empty no categories to update');
                return false;
            }
            $this->_addCategoryMenuTree($this->webgroup_categories,$saved_categories);
        }

        /**
         * Add all new categories, as WP terms, skip existing and leave existing
         *
         * Populate webgroup_ids with webgroup name to WP term id for easy lookup later in add products
         *
         * @param $categories
         *
         */
        public function _addCategoryData($category) {
            if(isset($category['DESC'])) {
                //If parent exists add that
                $parent_id = isset($this->webgroup_ids[$category['PARGROUP1']]) ? $this->webgroup_ids[$category['PARGROUP1']] : 0;

                //Get unique slug and cleaned 'term' name
                $term_info = false;
                list($term,$slug) = $this->_getTerm($category['DESC']);
                $siblings = get_terms($this->_category_taxonomy, array('fields' => 'all', 'get' => 'all', 'parent' => (int)$parent_id) );
                if(!empty($siblings)) {
                    foreach($siblings as $sibling) {
                        if($sibling->name == $term) {
                            $term_info = get_object_vars($sibling);
                            break;
                        }
                    }
                }
                //Save new term
                if($term_info === false) {
                    $term_info = $this->_createProductTerm($category['DESC'],$this->_category_taxonomy,$parent_id);
                }
                //Add thumbnail
                if(!empty($category['PICFILE'])) {
                    $attachment_id = $this->addThumbnailAttachment($term_info['term_id'],$category['PICFILE'],'category');
                    update_woocommerce_term_meta( $term_info['term_id'], 'thumbnail_id',$attachment_id);
                } else {
                    delete_woocommerce_term_meta( $term_info['term_id'], 'thumbnail_id');
                }
                //Stack the lookup ids
                $this->webgroup_ids[$category['WEBGROUP']] = (int)$term_info['term_id'];
            }
            return true;
        }

        /**
         * Create new term where one doesn't exists, for new categories or tags
         *
         * @param $name
         * @param string $taxonomy_name
         * @param int $parent_id the term id of parent 0 for none
         * @return int
         */
        private function _createProductTerm($name,$taxonomy_name =null,$parent_id = 0) {
            if(empty($taxonomy_name)) {
                $taxonomy_name = $this->_category_taxonomy;
            }
            //Use to ensure slug is unique to this spot in the hierarchy
            list($term,$slug) = $this->_getTerm($name);
            $args = array(
                'parent' => $parent_id,
                'slug' => $slug
            );
            $term_info = wp_insert_term($name, $taxonomy_name,$args);
            if(is_wp_error($term_info)) {
                error_log('Error inserting term '.$name.' '.$term_info->get_error_message());
                return 0;
            }
            if (!empty($term_info) )
                return $term_info;
            return 0;
        }

        /**
         * Based on wp_insert_term
         *
         * Gives us a fair indication of what the term will be like when sanitized
         * @param String $term
         * @param Int $unique_id an optional addition to uniquely identify the slug
         */
        private function _getTerm($term,$unique_id = null) {
            $term = apply_filters( 'pre_insert_term', $term, $this->_category_taxonomy);
            $args['name'] = $term;
            $args['taxonomy'] = $this->_category_taxonomy;
            $args = sanitize_term($args,  $this->_category_taxonomy, 'db');
            // expected_slashed ($name)
            $name = wp_unslash($args['name']);
            $slug = !empty($unique_id) ? $name.'-'.$unique_id : $name;
            $slug = sanitize_title($slug);
            return array($name,$slug);
        }

        /**
         * Build the product categories query API request,
         * set the required fields conditions and sort by
         *
         * @return array
         */
        private function _webgroupsParams( ) {
            $table = 'DRWGRP';
            $limit = '50';
            $sortBy = 'PARGROUP1';
            $offset = 1;

            $fields = array(
                'WEBGROUP',
                'DESC',
                'PICFILE',
                'WGRPFLAG1'
            );
            for($i = 1; $i <= 10; $i++) {
                $fields[] = 'PARGROUP'.$i;
            }

            $conditions = array(
                array(
                    'field' => 'PARGROUP1',
                    'comparison' => '!=',
                    'value' => ''
                ),
                array(
                    'field' => 'WGRPFLAG1',
                    'comparison' => '!=',
                    'value' => 'Y'
                )
            );
            return array($table,$fields,$conditions,$sortBy,$limit,$offset);
        }

        /**
         * Build a specific product query API request, set the required fields conditions and sort by
         *
         * @return array
         */
        private function _parentwebgroupsParams() {
            //Get defaults
            $table = 'DRWGRP';
            $limit = '50';
            $sortBy = 'PARGROUP1';
            $offset = 1;

            $fields = array(
                'WEBGROUP',
                'DESC',
                'PICFILE',
                'WGRPFLAG1'
            );
            for($i = 1; $i <= 10; $i++) {
                $fields[] = 'PARGROUP'.$i;
            }
            $conditions = array(
                array(
                    'field' => 'PARGROUP1',
                    'comparison' => 'equals',
                    'value' => ''
                ),
                array(
                    'field' => 'WGRPFLAG1',
                    'comparison' => '!=',
                    'value' => 'Y'
                )
            );
            return array($table,$fields,$conditions,$sortBy,$limit,$offset);
        }

        /**
         * Sort the webgroup array so that it has parent/root categories first,
         * child is always after parent.
         *
         * //TODO: Add support for multiple PARGROUP's
         *
         * @param Array $categories as returned by API when calling on DRWGRP table
         */
        private function _asortWebgroupData(&$categories) {
            $categories = $this->_stackArr('',$categories,array());
            return true;
        }

        /**
         * Recursive method to Sort the webgroup array so that it has parent/root categories first,
         * child is always after parent.
         *
         * @param String $current_parent the current parent webgroup name (empty for root).
         * @param Array $webgroups the original source data array as from the API.
         * @param Array $stacked_arr the new sorted array, stacked with ordered group results
         */
        private function _stackArr($current_parent,$webgroups,$stacked_arr) {
            $current_ret = array();
            $new_parents = array();
            foreach($webgroups as $group) {
                if(empty($current_parent)) {
                    //IF in the root ALL must be empty
                    $all_empty = true;
                    $idx = 1;
                    while(isset($group['PARGROUP'.$idx])) {

                        if($group['PARGROUP'.$idx] != $current_parent) {
                            $all_empty = false;
                            break;
                        }
                        $idx++;
                    }
                    if($all_empty) {
                        $current_ret[] = $group;
                        $new_parents[] = $group['WEBGROUP'];
                    }
                } else {
                    //If in sub/group then ensure parent exists
                    $idx = 1;
                    while(isset($group['PARGROUP'.$idx])) {
                        if($group['PARGROUP'.$idx] == $current_parent) {
                            $current_ret[] = $group;
                            $new_parents[] = $group['WEBGROUP'];
                            break;
                        }
                        $idx++;
                    }
                }
            }
            $stacked_arr = array_merge($stacked_arr,$current_ret);
            foreach($new_parents as $parent_str) {
                $stacked_arr = $this->_stackArr($parent_str,$webgroups,$stacked_arr);
            }
            return $stacked_arr;
        }


        /**
         * Re-builds the menu with the added categories
         * Checks existing menu items and won't re-add if one of same title found.
         * Any moved categories (terms) should have deleted all menu items so this step will re-add those and add new ones
         *
         * Assumes only 1 primary menu present
         *
         * @param array $categories
         */
        private function _addCategoryMenuTree($categories = array(),$saved_categories = array()) {
            global $wpdb;
            require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

            $primary_slug = 'primary';
            $locations = get_nav_menu_locations();
            if(!isset( $locations[$primary_slug])) {
                //can't find menu to add
                return false;
            }
            $menu = wp_get_nav_menu_object( $locations[$primary_slug]);
            $menu_items = wp_get_nav_menu_items( $menu->term_id, array( 'update_post_term_cache' => false ) );

            //Store the parent object names to their menu ids
            //Try to do efficiently, leave existing if still current and doesn't need updating, only insert new where required, then only delete unused
            $added_items = array();
            $used_menu_ids = array();
            foreach($categories as $category) {
                if(!isset($this->webgroup_ids[$category['WEBGROUP']])) {
                    error_log('Error menu item been skipped not found '.$category['WEBGROUP']);
                    //Something is wrong
                    continue;
                }

                //check not still in their and find parent if that is in there
                $parent_id = isset($this->webgroup_ids[$category['PARGROUP1']]) ? $this->webgroup_ids[$category['PARGROUP1']] : 0;
                $parent_nav_item_id = null;
                $skip = false;
                foreach($menu_items as $idx => $menu_item) {
                    if($menu_item->title == $category['DESC']
                    && $menu_item->object_id == $this->webgroup_ids[$category['WEBGROUP']]
                    && (empty($parent_id) || $menu_item->post_parent == $parent_id)) {
                        //This is shit but a valid state, where a parent can be removed and the childs parent ref isn't deleted,
                        //these orphaned menu items should be removed
                        if(!empty($menu_item->menu_item_parent)) {
                            $remove_and_dont_skip = true;
                            foreach($menu_items as $sub_menu_item) {
                                if($sub_menu_item->ID == $menu_item->menu_item_parent) {
                                    $remove_and_dont_skip  = false;
                                    break;
                                }
                            }
                            if($remove_and_dont_skip) {
                                wp_delete_post( $menu_item->ID, true );
                                unset($menu_items[$idx]);
                                continue;
                            }
                        }
                        //Really annoying but we need to stack every parent grandparent etc in the list
                        if(in_array($this->webgroup_ids[$category['WEBGROUP']],$saved_categories)) {
                            $used_menu_ids[] = $menu_item->ID;
                            //$this->_saveUsedMenuItem($menu_item->ID,$menu_item->menu_item_parent,$menu_items,$used_menu_ids);
                        }
                        $skip = true;
                    }
                    //Check past menu items AND newly added ones in $added_items
                    if(!empty($parent_id) && $parent_id == $menu_item->object_id) {
                        $parent_nav_item_id = $menu_item->ID;
                    }
                }
                if($skip) { continue; }


                //If not found see if we've recently added it
                if(empty($parent_nav_item_id)) {
                    if(isset($added_items[$category['PARGROUP1']])) {
                        $parent_nav_item_id = $added_items[$category['PARGROUP1']];
                    }
                }

                //Add lookup and add posts to menu
                $args = array(
                    'menu-item-object-id' => $this->webgroup_ids[$category['WEBGROUP']],
                    'menu-item-object' => $this->_category_taxonomy,
                    'menu-item-type' => 'taxonomy',
                    'menu-item-title' => $category['DESC'],
                    'menu-item-url' => '',
                    'menu-item-status' =>  'publish',
                    'menu-item-parent-id' => (!empty($parent_nav_item_id) ? $parent_nav_item_id : 0)
                );

                $item_id = wp_update_nav_menu_item(  $menu->term_id, 0,$args);
                if(!is_wp_error($item_id)) {
                    //Manually create relationship
                    $wpdb->insert($wpdb->term_relationships, array(
                        "object_id" => $item_id,
                        "term_taxonomy_id" => $menu->term_id
                    ), array("%d", "%d"));
                    $added_items[$category['WEBGROUP']] = $item_id;
                }
                //If this is one where a product was saved ensure we mark this menu item and all its parents for non-deletion
                if(in_array($this->webgroup_ids[$category['WEBGROUP']],$saved_categories)) {
                    $used_menu_ids[] = $item_id;
                    //$this->_saveUsedMenuItem($item_id,$parent_nav_item_id,$menu_items,$used_menu_ids);
                }
            }
            //Delete unused menu items (some may have just been added!)
            $menu_items = wp_get_nav_menu_items( $menu->term_id, array( 'update_post_term_cache' => true ) );
            foreach($menu_items as $menu_item) {
                if($menu_item->object != $this->_category_taxonomy || $menu_item->type != 'taxonomy') {
                    continue;
                }
                if(!in_array($menu_item->ID,$used_menu_ids)) {
                    wp_delete_post( $menu_item->ID, true );
                }
            }
            $this->_reorderMenu($menu);

            set_theme_mod( 'nav_menu_locations', array_map( 'absint', array($primary_slug => (int)$menu->term_id)) );
        }

        /*
         * After complete creation of the menu reorder
         * in alphabetical order for products but leave for the standard menu items
         * @param Object $menu
         */
        private function _reorderMenu($menu) {
            global $wpdb;

            //Need to regrab as its been updated and items removed
            $menu_items = wp_get_nav_menu_items( $menu->term_id);

            $product_menu_titles = array();
            $standard_menu_order = array();
            foreach($menu_items as $item) {
                //Separate out the product and standard menu so leave standard alone
                if($item->object != 'product_cat') {
                    $standard_menu_order[$item->ID] = $item->menu_order;
                } else {
                    $product_menu_titles[$item->ID] = $item->title;
                }
            }
            //Sort by title
            asort($product_menu_titles);
            $prod_inc = 0;
            //Order products by the alphabet
            foreach($product_menu_titles as $id => $menu_title) {
                //Ensure Products appear first
                $data = array('menu_order' => $prod_inc);
                $where = array( 'ID' => $id );
                $wpdb->update( $wpdb->posts, $data, $where );
                $prod_inc++;
            }
            //Leave the standard menu order, but ensure after products
            foreach($standard_menu_order as $id => $menu_order) {
                $data = array('menu_order' => ($menu_order + $prod_inc));
                $where = array('ID' => $id);
                $wpdb->update( $wpdb->posts, $data, $where);
            }
        }

        /**
         * Annoying function to save all the parents of a menu item as 'used' so we don't delete them
         *
         * @param $menu_item_id current menu item
         * @param $menu_item_parent_id its immediate parent (if any)
         * @param $menu_items list of all existing menu items
         * @param $used_menu_ids array of already stored used menu ids
         */
        private function _saveUsedMenuItem($menu_item_id,$menu_item_parent_id,$menu_items,&$used_menu_ids) {
            $used_menu_ids[] = $menu_item_id;
            //If not already stored then go through the entire tree
            $parent_id = $menu_item_parent_id;
            if(!empty($parent_id) && !in_array($parent_id,$used_menu_ids)) {
                $used_menu_ids[] = $parent_id;
                while(!empty($parent_id)) {
                    $parent_lookup = $parent_id;
                    $parent_id = null;
                    foreach($menu_items as $reloop_menu_item) {
                        if($reloop_menu_item->ID == $parent_lookup) {
                            $parent_id = $reloop_menu_item->menu_item_parent;
                            $used_menu_ids[] = $parent_id;
                            break;
                        }
                    }
                }
            }
        }



        /**
         * Based upon wordpress get_term_by
         * This allows to get all terms by name or slug, not limited to one return, but not as heavy as wp get_term
         *
         * @see get_term_by()
         *
         * @param string $field Either 'slug', 'name', or 'id'
         * @param string|int $value Search for this term value
         * @param string $taxonomy Taxonomy Name
         * @param string $output Constant OBJECT, ARRAY_A, or ARRAY_N
         * @param string $filter Optional, default is raw or no WordPress defined filter will applied.
         * @param int $limit Optional, default no limit, set a limit on number of returns
         * @return mixed Term Row from database. Will return false if $taxonomy does not exist or $term was not found.
         *
         */
        private function _getTermsBy($field, $value, $taxonomy, $filter = 'raw',$limit = -1) {
            global $wpdb;

            if ( ! taxonomy_exists($taxonomy) )
                return false;

            if ( 'slug' == $field ) {
                $field = 't.slug';
                $value = sanitize_title($value);
                if ( empty($value) )
                    return false;
            } else if ( 'name' == $field ) {
                // Assume already escaped
                $value = wp_unslash($value);
                $field = 't.name';
            } else {
                $value = wp_unslash($value);
                $field = 't.term_id';
            }
            $limit = $limit > 0 ? 'LIMIT '.$limit : '';
            $terms = $wpdb->get_results( $wpdb->prepare( "SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy = %s AND $field = %s".$limit, $taxonomy, $value) );
            if ( empty($terms) )
                return false;

            $ret = array();
            foreach($terms as $term) {
                wp_cache_add($term->term_id, $term, $taxonomy);
                $term = apply_filters('get_term', $term, $taxonomy);
                $term = apply_filters("get_$taxonomy", $term, $taxonomy);
                $term = sanitize_term($term, $taxonomy, $filter);
                $ret[] = $term;
            }
            return $ret;
        }

    }

endif;
