<?php
class ControllerExtensionModuleCatalog2 extends Controller
{
	public function index()
	{
		$this->load->language('extension/module/category');

		if (isset($this->request->get['path'])) {
			$parts = explode('_', (string)$this->request->get['path']);
		} else {
			$parts = array();
		}

		if (isset($parts[0])) {
			$data['category_id'] = $parts[0];
		} else {
			$data['category_id'] = 0;
		}

		if (isset($parts[1])) {
			$data['child_id'] = $parts[1];
		} else {
			$data['child_id'] = 0;
		}

		$this->load->model('catalog/category');

		$this->load->model('catalog/product');

		$data['categories'] = array();

		$cats = [
			179 => [
				'name' => 'Диваны',
				'order' => 2,
			],
			185 => [
				'name' => 'Кресла',
				'order' => 1,
			],
			193 => [
				'name' => 'Стулья',
				'order' => 3,
			],
			199 =>  [
				'name' => 'Пуфы и банкетки',
				'order' => 6,
			],
			204 => [
				'name' => 'Кровати',
				'order' => 4,
			], 
			217 => [
				'name' => 'Тумбы и комоды',
				'order' => 9,
			],
			225 => [
				'name' => 'Столы',
				'order' => 7,
			],
			232 => [
				'name' => 'Матрасы',
				'order' => 5,
			], 
			245 => [
				'name' => 'Аксессуары',
				'order' => 8,
			]
		];

		$categories = $this->model_catalog_category->getCategories(0, array_keys($cats));

		foreach ($categories as $category) {
			$children_data = array();

			if ($category['category_id'] == $data['category_id']) {
				$children = $this->model_catalog_category->getCategories($category['category_id']);

				foreach ($children as $child) {
					$filter_data = array('filter_category_id' => $child['category_id'], 'filter_sub_category' => true);

					$children_data[] = array(
						'category_id' => $child['category_id'],
						'name' => $child['name'], // . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
						'href' => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'])
					);
				}
			}

			$filter_data = array(
				'filter_category_id'  => $category['category_id'],
				'filter_sub_category' => true
			);
			$results = $this->model_catalog_product->getProducts(array_merge(
				$filter_data,
				[
					'limit' => 8,
					'start' => 0,
					'show_cat' => true
				]
			));
			$wishList = $this->model_account_wishlist->getWishlist();

			$products = [];

			foreach ($results as $result) {
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				if ((float)$result['special']) {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$special = false;
				}

				if ($result['price'] && (float)$result['special']) {
					$percent =  "-" . (100 - round(((float)$result['special'] / $result['price']) * 100)) . " %";
				} else {
					$percent = "";
				}

				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
				} else {
					$tax = false;
				}

				if ($this->config->get('config_review_status')) {
					$rating = (int)$result['rating'];
				} else {
					$rating = false;
				}

				$images = $this->model_catalog_product->getProductImages($result['product_id']);
				$other_thumbs = [];
				$id_other_thumbs = 1;

				if (count($images) > 0) {
					foreach ($images as $im) {
						$other_thumbs[] = [
							'path' => $this->model_tool_image->resize($im['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height')),
							'class_1' => 'other_thumb',
							'id' => $id_other_thumbs,
						];
						$id_other_thumbs++;
					}
				}

				$in_wishlist = false;

				foreach ($wishList as $wish) {

					if ($result['product_id'] == $wish['product_id']) {
						$in_wishlist = 'inWishlist';
					}
				}

				$attribute_groups = $this->model_catalog_product->getProductAttributes($result['product_id']);

				$sized_attr = false;

				if (count($attribute_groups) > 0) {
					foreach ($attribute_groups as $attgroup) {
						foreach ($attgroup["attribute"] as $attr) {
							if ($attr["attribute_id"] == "13") {
								$sized_attr = $attr;
							}
						}
					}
				}
				$sizes = false;
				$product_option_value_data = [];

				foreach ($this->model_catalog_product->getProductOptions($result['product_id']) as $option) {
					if ($option['name'] == 'Цвет') {
						foreach ($option['product_option_value'] as $option_value) {
							if (!$option_value['subtract'] || ($option_value['quantity'] > 0)) {

								$product_option_value_data[] = array(
									'product_option_value_id' => $option_value['product_option_value_id'],
									'selected' 				  => $option_value['selected'],
									'option_value_id'         => isset($option_value['option_value_id']) ? $option_value['option_value_id'] : null,
									'name'                    => $option_value['name'],
									'variation_product'		  => $option_value['variation_product'],
									'image'                   => $this->model_tool_image->resize($option_value['image'], 30, 30),
								);
							}
						}
					}
				}

				$products[] = array(
					'product_id'  => $result['product_id'],
					'thumb'       => $image,
					'other_thumbs' => $other_thumbs,
					'colors'	  => $product_option_value_data,
					'name'        => $result['name'],
					'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
					'price'       => $price,
					'special'     => $special,
					'in_wishlist' => $in_wishlist,
					'percent'     => $percent,
					'sized_attr'  => $sized_attr,
					'tax'         => $tax,
					'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
					'rating'      => $result['rating'],
					'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'])
				);
			}

			$data['categories'][] = array(
				'order' => $cats[$category['category_id']]['order'],
				'category_id' => $category['category_id'],
				'name'        => $category['name'], // . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
				'children'    => $children_data,
				'href'        => "#" . $category['category_id'], //$this->url->link('product/category', 'path=' . $category['category_id']),
				'products' 	  => $products,
			);
		}
		usort($data['categories'], function($a, $b){
			if($a['order'] == $b['order'] ){
				return 0;
			}

			return ($a['order'] < $b['order']) ? -1 : 1;
		});

		return $this->load->view('extension/module/category', $data);
	}
}
