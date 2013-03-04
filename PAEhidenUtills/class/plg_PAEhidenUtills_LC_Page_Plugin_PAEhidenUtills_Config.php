<?php
/*
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */
// {{{ requires
require_once CLASS_EX_REALDIR . 'page_extends/admin/LC_Page_Admin_Ex.php';

/**
 * プラグイン設定ページ
 *
 * @package PAEhidenUtills
 * @author PlugAid inc.
 * @version $Id$
 */
class LC_Page_Plugin_PAEhidenUtills_Config extends LC_Page_Admin_Ex {

    var $arrForm = array();

    /**
     * 初期化する.
     *
     * @return void
     */
    function init() {
        parent::init();
        $this->tpl_mainpage = PLUGIN_UPLOAD_REALDIR . 'PAEhidenUtills/templates/plg_PAEhidenUtills_config.tpl';
        $this->tpl_subtitle = "E飛伝IIユティーリティープラグインPA";
        $this->tpl_mode = $this->getMode();
        // 配送業者の取得
        $this->arrDeliv = SC_Helper_DB_Ex::sfGetIDValueList('dtb_deliv', 'deliv_id', 'name');
    }

    /**
     * プロセス.
     *
     * @return void
     */
    function process() {
        $this->action();
        $this->sendResponse();
    }

    /**
     * Page のアクション.
     *
     * @return void
     */
    function action()
    {
        if (!empty($_POST)) {

            $objFormParam = new SC_FormParam_Ex();
            $this->lfInitParam($objFormParam, $_POST);
            $objFormParam->setParam($_POST);
            $objFormParam->convParam();

            $this->arrErr = $this->lfCheckError($objFormParam);
            $post = $objFormParam->getHashArray();

            $this->arrForm = $post;

            if (count($this->arrErr) == 0) {
                PAEhidenUtills::saveConfig($this->arrForm);
                $this->tpl_onload = "window.alert('設定を変更しました。');";
            }
        } else {
            $this->arrForm = PAEhidenUtills::loadConfig();
        }
        
        
        // 初期ページ表示
        $this->setTemplate($this->tpl_mainpage);
    }

    /**
     * デストラクタ.
     *
     * @return void
     */
    function destroy()
    {
        parent::destroy();
    }
    
    
    
    function lfInitParam(&$objFormParam, $post) {
        
        $objFormParam->addParam(
                '受注番号を含める', 
                'order_id_flg', 
                INT_LEN, 
                'n', 
                array('SPTAB_CHECK', 'NUM_CHECK', 'MAX_LENGTH_CHECK')
        );
        
        $objFormParam->addParam(
                '受注番号のprefix', 
                'order_id_prefix', 
                4, 
                'KVna', 
                array('ALNUM_CHECK', 'SPTAB_CHECK','MAX_LENGTH_CHECK')
        );
        
        $objFormParam->addParam(
                '対応する配送業者設定', 
                'deliv_id', 
                INT_LEN, 
                'KVna', 
                array('SPTAB_CHECK', 'NUM_CHECK', 'MAX_LENGTH_CHECK')
        );
        
        
        $objFormParam->addParam(
                '出荷履歴データのインポートを利用する', 
                'import_flg', 
                INT_LEN, 
                'n', 
                array('SPTAB_CHECK', 'NUM_CHECK', 'MAX_LENGTH_CHECK')
        );
        
        $objFormParam->addParam(
                'お問い合わせNo.を保存する', 
                'toiban_flg', 
                INT_LEN, 
                'n', 
                array('SPTAB_CHECK', 'NUM_CHECK', 'MAX_LENGTH_CHECK')
        );
        
        $objFormParam->addParam(
                '出荷完了メールを自動で送信する', 
                'mail_flg', 
                INT_LEN, 
                'n', 
                array('SPTAB_CHECK', 'NUM_CHECK', 'MAX_LENGTH_CHECK')
        );
        
//        $objFormParam->addParam(
//                '出荷完了メールテンプレート', 
//                'mail_template_id', 
//                INT_LEN, 
//                'n', 
//                array('SPTAB_CHECK', 'NUM_CHECK', 'MAX_LENGTH_CHECK')
//        );
        
    }
    
    // 入力エラーチェック
    function lfCheckError(&$objFormParam) {
        $arrErr = $objFormParam->checkError();
        return $arrErr;
    }
    
    
    

}
