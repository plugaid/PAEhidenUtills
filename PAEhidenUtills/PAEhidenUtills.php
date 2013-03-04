<?php

/*
 * PAEhidenUtills
 * Copyright (C) 2012 PlugAid inc. All Rights Reserved.
 * http://plug-aid.jp/
 * 
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * $Id: PAEhidenUtills.php 92 2013-03-02 11:32:42Z t.takabayashi $
 */
class PAEhidenUtills extends SC_Plugin_Base {
    
    const PLUGIN_CODE = 'PAEhidenUtills';

    static $arrConfig = null;
    
    /**
     * コンストラクタ
     * プラグイン情報(dtb_plugin)をメンバ変数をセットします.
     */
    public function __construct(array $arrSelfInfo) {
        parent::__construct($arrSelfInfo);
    }

    function install($arrPlugin) {

		$objQuery =& SC_Query_Ex::getSingletonInstance();
        $masterData = new SC_DB_MasterData_Ex();

        $objQuery->begin();
        
        $arrConfig = PAEhidenUtills::loadConfig();
        
        // メールテンプレート追加
        if (empty($arrConfig['mail_template_id'])) {
            $res = $objQuery->select('max(id) as id, max(rank) as rank', 'mtb_mail_template');
            if ($res) {
                $mail_template_id = (int)$res[0]['id'] + 1;
                $mail_template_rank = (int)$res[0]['rank'];
            } else {
                $mail_template_id = 1;
                $mail_template_rank = 1;
            }

            $data = array(
                'id' => $mail_template_id,
                'rank' => $mail_template_rank,
                'name' => 'E飛伝II 発送完了メール',
            );
            $objQuery->insert('mtb_mail_template', $data);
            $arrConfig['mail_template_id'] = $mail_template_id;
        }
        
        // メールテンプレートパス追加
        $res = $objQuery->count('mtb_mail_tpl_path', 'id=?', array($arrConfig['mail_template_id']));
        if (!$res) {
            $rank = $objQuery->getOne('SELECT max(rank) FROM mtb_mail_tpl_path');
            $data = array(
                'id' => $arrConfig['mail_template_id'],
                'rank' => (int)$rank + 1,
                'name' => 'mail_templates/order_mail.tpl',
            );
            $objQuery->insert('mtb_mail_tpl_path', $data);
        }
        
        // メールテンプレート追加
        $res = $objQuery->count('dtb_mailtemplate', 'template_id=?', array($arrConfig['mail_template_id']));
        if (!$res) {
            $data = array(
                'template_id' => $arrConfig['mail_template_id'],
                'subject' => '商品を発送いたしました',
                'header' => 'この度はご注文いただき誠にありがとうございます。下記商品を発送いたしましたのでご確認ください。',
                'footer' => '',
                'creator_id' => 0,
                'del_flg' => 0,
                'create_date' => 'CURRENT_TIMESTAMP',
                'update_date' => 'CURRENT_TIMESTAMP',
            );
            $objQuery->insert('dtb_mailtemplate', $data);
        }

        $masterData->clearCache('mtb_mail_template');
        $masterData->clearCache('mtb_mail_tpl_path');

        
		
        // デフォルト設定
		// 受注番号を含める
		$arrConfig['order_id_flg'] = 1;
		// 受注番号のprefix
		$arrConfig['order_id_prefix'] = '';
		// 出荷履歴データのインポートを利用する
		$arrConfig['import_flg'] = 1;
		// お問い合わせNo.を保存する
		$arrConfig['toiban_flg'] = 1;
		// 出荷完了メールを自動で送信する
		$arrConfig['mail_flg'] = 1;

		
		// 対応する配送業者設定
		$arrConfig['deliv_id'] = array();
		// 配送業者の取得
        $arrDeliv = SC_Helper_DB_Ex::sfGetIDValueList('dtb_deliv', 'deliv_id', 'name');
		if ($arrDeliv) {
			// 佐川を探す
			foreach ($arrDeliv as $id => $name) {
				if (preg_match('/佐川/', $name)) {
					$arrConfig['deliv_id'][] = $id;
				}
			}
		}
        
        self::saveConfig($arrConfig);        
        $objQuery->commit();
        
        
        
        //　ファイルをコピー
        $install_info = array(
			'copy' => array(),
			'errors' => array(),
		);
        
        foreach (self::getCopyFiles() as $file) {
            if (@copy($file['src'], $file['dst'])) {
                $install_info['copy'][] = basename($file['src']) . 'をコピーしました';
			} else {
                $install_info['errors'][] = basename($file['src']) . 'のコピーに失敗しました';
            }
        }
        
        self::saveInstallInfo($install_info);
    }

    function uninstall($arrPlugin) {
		$objQuery =& SC_Query_Ex::getSingletonInstance();
        $arrConfig = PAEhidenUtills::loadConfig();
        $objQuery->begin();
        // メールテンプレート削除
        $mail_template_id = (int)$arrConfig['mail_template_id'];
        if ($mail_template_id) {
            $objQuery->delete('mtb_mail_template', 'id=?', array($mail_template_id));
            $objQuery->delete('mtb_mail_tpl_path', 'id=?', array($mail_template_id));
            $objQuery->delete('dtb_mailtemplate', 'template_id=?', array($mail_template_id));
        }
        $masterData = new SC_DB_MasterData_Ex();
        $masterData->clearCache('mtb_mail_template');
        $masterData->clearCache('mtb_mail_tpl_path');
        
        // コピーしたファイルの削除
        foreach (self::getCopyFiles() as $file) {
            @unlink($file['dst']);
        }
        

    }

    /**
	 * インストール時にコピーするファイルの情報を返す
	 * @return type
	 */
    static function getCopyFiles() {
        return array(
            array(
                'src' => PLUGIN_UPLOAD_REALDIR . self::PLUGIN_CODE . "/logo.png",
                'dst' => PLUGIN_HTML_REALDIR . self::PLUGIN_CODE . "/logo.png"
            ),
            array(
                'src' => PLUGIN_UPLOAD_REALDIR . self::PLUGIN_CODE . "/copy/plg_paehidenutills_import.php",
                'dst' => HTML_REALDIR . "admin/order/plg_paehidenutills_import.php"
            ),
        );
    }
    
    function enable($arrPlugin) {
        // nop
    }

    function disable($arrPlugin) {
        // nop
    }

    /**
     * 処理の介入箇所とコールバック関数を設定
     * registerはプラグインインスタンス生成時に実行されます
     *
     * @param SC_Helper_Plugin $objHelperPlugin
     */
    function register(SC_Helper_Plugin $objHelperPlugin) {
//        require_once CLASS_EX_REALDIR . 'db_extends/SC_DB_MasterData_Ex.php';
        
        
        // e飛伝Ⅱ用出荷データCSV出力フックポイント
        $objHelperPlugin->addAction('LC_Page_Admin_Order_action_after', array($this, 'afterActionAdminOrder'));
        $objHelperPlugin->addAction('prefilterTransform', array(&$this, 'prefilterTransform'), 1);
    }

    function preProcess(LC_Page_EX $objPage) {
        
    }

    function process(LC_Page_EX $objPage) {
        switch (get_class($objPage)) {
            case 'LC_Page_Index_Ex':
                break;
        }
    }

    /**
     * 商品詳細画面 beforeフック処理
     */
    function beforeActionProductsDetail(LC_Page_Admin_Order_EX $objPage) {
        
    }

    /**
     *  afterフック処理
     */
    function afterActionAdminOrder(LC_Page_Admin_Order_EX $objPage) {
		if($objPage->getMode()=='ehiden_csv'){
            require_once dirname(__FILE__) . '/class/plg_PAEhidenUtills_CreateCSV.php';
            plg_PAEhidenUtills_CreateCSV::create($objPage);
    		exit;
        }
    }

    /**
     * 
     * @param type $source
     * @param LC_Page_Ex $objPage
     * @param type $filename
     */
    function prefilterTransform(&$source, LC_Page_Ex $objPage, $filename) {
		$config = self::loadConfig();
        $objTransform = new SC_Helper_Transform($source);
        $class_name = get_class($objPage);
        
        $template_dir = dirname(__FILE__) . '/templates/';
        
        switch ($objPage->arrPageLayout['device_type_id']) {
            case DEVICE_TYPE_MOBILE:
            case DEVICE_TYPE_SMARTPHONE:
            case DEVICE_TYPE_PC:
                break;
            case DEVICE_TYPE_ADMIN:
            default:
                if(!empty($config['import_flg']) 
						&& preg_match('/^LC_Page_Admin.*_Ex$/', $class_name)) {
                    $content = file_get_contents($template_dir . 'admin/plg_PAEhidenUtills_common_script.tpl');
                    $objTransform->select('body')->appendChild($content);
                    $source = $objTransform->getHTML();
                }
                
                
                
                // 受注一覧画面
                if (strpos($filename, 'order/index.tpl') !== false) {
                    // 受注検索結果画面にe飛伝Ⅱ用のCSVダウンロードボタンを追加する
                    $content = file_get_contents($template_dir . 'admin/order/plg_PAEhidenUtills_ehidencsv.tpl');
                    $objTransform->select('div\btn a', 3)->insertAfter($content);
                }
                break;
        }
        $source = $objTransform->getHTML();
    }

    
    
    /**
     * 設定データを読み込む
     * @return array
     */
    static function loadConfig() {
        if (is_null(self::$arrConfig)) {
            self::$arrConfig = array();
            $objQuery =& SC_Query_Ex::getSingletonInstance();
            $data = $objQuery->getOne('SELECT free_field1 FROM dtb_plugin WHERE plugin_code = ?', array(self::PLUGIN_CODE));
            if ($data) {
                $arr = @unserialize($data);
                if ($arr && is_array($arr)) {
                    self::$arrConfig = $arr;
                }
            }
        }
        return self::$arrConfig;
    }
    
    /**
     * 設定データを保存する
     * @param array $arrData
     */
	static function saveConfig($arrConfig){
        self::loadConfig();

        self::$arrConfig = (array)$arrConfig + self::$arrConfig;
        
		$objQuery =& SC_Query_Ex::getSingletonInstance();
        $data = array('free_field1' => serialize(self::$arrConfig));
		$objQuery->update(
                "dtb_plugin",
                $data,
                "plugin_code = ?",
                array(self::PLUGIN_CODE)
        );
	}
	
	/**
	 * インストール情報を読み込んで返す
	 * @return array
	 */
	static function loadInstallInfo() {
        $info = array();
        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $data = $objQuery->getOne(
				'SELECT free_field2 FROM dtb_plugin WHERE plugin_code = ?', 
				array(self::PLUGIN_CODE)
		);
        if ($data) {
            $arr = @unserialize($data);
            if ($arr && is_array($arr)) {
                $info = $arr;
            }
        }
        return $info;
		
	}
    
    /**
	 * インストール情報を保存する
	 * @param array $install_info
	 */
    static function saveInstallInfo($install_info) {
		$objQuery =& SC_Query_Ex::getSingletonInstance();
        $data = array('free_field2' => serialize($install_info));
		$objQuery->update(
                "dtb_plugin",
                $data,
                "plugin_code = ?",
                array(self::PLUGIN_CODE)
        );
        
    }
    
}

if (!function_exists('pr')) {
    function pr($var) {
        echo '<pre>'; print_r($var); echo '</pre>';
    }
}
