<?php

/**
 * 
 * $Id$
 */
class plg_PAEhidenUtills_CreateCSV {
    static $cols = array(
        "住所録コード",
        "お届け先電話番号",
        "お届け先郵便番号",
        "お届け先住所１",
        "お届け先住所２",
        "お届け先住所３",
        "お届け先名称１",
        "お届け先名称２",
        "お客様管理ナンバー",
        "お客様コード",
        "部署・担当者",
        "荷送人電話番号",
        "ご依頼主電話番号",
        "ご依頼主郵便番号",
        "ご依頼主住所１",
        "ご依頼主住所２",
        "ご依頼主名称１",
        "ご依頼主名称２",
        "荷姿コード",
        "品名１",
        "品名２",
        "品名３",
        "品名４",
        "品名５",
        "出荷個数",
        "便種(スピードで選択)",
        "便種(商品)",
        "配達日",
        "配達指定時間帯",
        "配達指定時間（時分）",
        "代引金額",
        "消費税",
        "決済種別",
        "保険金額",
        "保険金額印字",
        "指定シール①",
        "指定シール②",
        "指定シール③",
        "営業店止め",
        "ＳＲＣ区分",
        "営業店コード",
        "元着区分",
    );

    
    
    
    static function create(LC_Page_Admin_Order_EX $objPage) {
        $objFormParam = new SC_FormParam_Ex();
        LC_Page_Admin_Order::lfInitParam($objFormParam);
        $objFormParam->setParam($_POST);

        $objFormParam->convParam();
        $objFormParam->trimParam();

        $where = 'del_flg = 0';
        $arrWhereVal = array();
        foreach ($objPage->arrHidden as $key => $val) {
            if ($val == '') {
                continue;
            }

            LC_Page_Admin_Order::buildQuery($key, $where, $arrWhereVal, $objFormParam);

        }

        $order = 'update_date DESC';

//        if ($where != '') {
//            $where = " WHERE $where ";
//        }

        // CSV出力
        self::downloadCsv($where, $arrWhereVal, $order);
    }
    
    /**
     * E飛伝II用インポートCSVファイルを送信する
     *
     * @param string $where WHERE条件文
     * @param array $arrVal プリペアドステートメントの実行時に使用される配列。配列の要素数は、クエリ内のプレースホルダの数と同じでなければなりません。
     * @param string $order ORDER文
     * @return mixed $is_download = true時 成功失敗フラグ(boolean) 、$is_downalod = false時 string
     */
    static function downloadCsv($where = '', $arrVal = array(), $order = '') {
        
        $config = PAEhidenUtills::loadConfig();

    	$objCSV = new SC_Helper_CSV_Ex();
    	// 実行時間を制限しない
    	@set_time_limit(0);
        

        $objQuery =& SC_Query_Ex::getSingletonInstance();
        $objQuery->setOrder($order);
        $arrOrder = $objQuery->select('order_id', 'dtb_order', $where, $arrVal);
        $arrOrder = SC_Utils_Ex::sfSwapArray($arrOrder);
        $sql = 'SELECT'
            . ' dtb_order.order_id AS order_id'
            . ',dtb_order.order_name01 || dtb_order.order_name02 AS order_name'
            . ',dtb_order.order_kana01 || dtb_order.order_kana02 AS order_kana'
            . ',dtb_order.order_tel01 || dtb_order.order_tel02 || dtb_order.order_tel03 AS order_tel'
            . ',dtb_order.order_fax01 || dtb_order.order_fax02 || dtb_order.order_fax03 AS order_fax'
            . ',pref1.name AS order_pref'
            . ',dtb_order.order_zip01 || dtb_order.order_zip02 AS order_zip'
            . ',dtb_order.order_addr01 || dtb_order.order_addr02 AS order_addr'
            . ',dtb_order.subtotal AS subtotal'
            . ',dtb_order.discount AS discount'
            . ',dtb_order.deliv_id AS deliv_id'
            . ',dtb_order.deliv_fee AS deliv_fee'
            . ',dtb_order.charge AS charge'
            . ',dtb_order.tax AS tax'
            . ',dtb_order.total AS total'
            . ',dtb_order.payment_total AS payment_total'
            . ',dtb_order.payment_id AS payment_id'
            . ',dtb_order.payment_method AS payment_method'
            . ',dtb_shipping.shipping_id AS shipping_id'
            . ',dtb_shipping.shipping_name01 || dtb_shipping.shipping_name02 AS shipping_name'
            . ',dtb_shipping.shipping_kana01 || dtb_shipping.shipping_kana02 AS shipping_kana'
            . ',dtb_shipping.shipping_tel01 || dtb_shipping.shipping_tel02 || dtb_shipping.shipping_tel03 AS shipping_tel'
            . ',dtb_shipping.shipping_fax01 || dtb_shipping.shipping_fax02 || dtb_shipping.shipping_fax03 AS shipping_fax'
            . ',pref2.name AS shipping_pref'
            . ',dtb_shipping.shipping_zip01 || dtb_shipping.shipping_zip02 AS shipping_zip'
            . ',dtb_shipping.shipping_addr01 || dtb_shipping.shipping_addr02 AS shipping_addr'
            . ',dtb_shipping.time_id AS time_id'
            . ',dtb_shipping.shipping_id AS shipping_id'
            . ',dtb_shipping.shipping_time AS shipping_time'
            . ',dtb_shipping.shipping_num AS shipping_num'
            . ',dtb_shipping.shipping_date AS shipping_date'
            . ',dtb_shipping.shipping_commit_date AS shipping_commit_date'
            . ',dtb_shipping.shipping_time AS shipping_time'
            . ' FROM dtb_order'
            . ' LEFT JOIN dtb_shipping USING (order_id)'
            . ' LEFT JOIN mtb_pref AS pref1 ON (pref1.id = dtb_order.order_pref)'
            . ' LEFT JOIN mtb_pref AS pref2 ON (pref2.id = dtb_shipping.shipping_pref)'
            . ' WHERE dtb_order.order_id IN (' . implode(',', $arrOrder['order_id']) . ')'
            . ' AND dtb_shipping.del_flg=0';
        
        $arr = $objQuery->getAll($sql);
        $arrData = array();
        
        $arrData[] = self::$cols;
        
        foreach ($arr as $order) {
            $data = array();
            
            $arrShipping = $objQuery->select('*', 'dtb_shipping',  'order_id=?', array($order['order_id']));
            
            //0 住所録コード
            $data[0] = '';
            //1 お届け先電話番号
            $data[1] = mb_convert_kana($order['shipping_tel'], 'a');
            //2 お届け先郵便番号
            $data[2] = mb_convert_kana($order['shipping_zip'], 'a');
            //3 お届け先住所１
            $data[3] = mb_substr($order['shipping_addr'], 0, 16, 'utf-8');
            //4 お届け先住所２
            $data[4] = mb_substr($order['shipping_addr'], 16, 16, 'utf-8');
            //5 お届け先住所３
            $data[5] = mb_substr($order['shipping_addr'], 32, 16, 'utf-8');
            //6 お届け先名称１
            $data[6] = mb_substr($order['shipping_name'], 0, 16, 'utf-8');
            //7 お届け先名称２
            $data[7] = mb_substr($order['shipping_name'], 16, 16, 'utf-8');
            //8 お客様管理ナンバー
            if (!empty($config['order_id_flg'])) {		
                $number = $order['order_id'] . '_' . $order['shipping_id'];
                if (!empty($config['order_id_prefix'])) {
                    $number = $config['order_id_prefix'] . '_' . $number;
                }
            } else {
                $number = '';
            }
            $data[8] = $number;
            //9 お客様コード
            $data[9] = '';
            //10    部署・担当者
            $data[10] = '';
            //11    荷送人電話番号
            $data[11] = '';
            //12    ご依頼主電話番号
            $data[12] = mb_convert_kana($order['order_tel'], 'a');
            //13    ご依頼主郵便番号
            $data[13] = mb_convert_kana($order['order_zip'], 'a');
            //14    ご依頼主住所１
            $data[14] = mb_substr($order['order_addr'], 0, 16, 'utf-8');
            //15    ご依頼主住所２
            $data[15] = mb_substr($order['order_addr'], 16, 16, 'utf-8');
            //16    ご依頼主名称１
            $data[16] = mb_substr($order['order_name'], 0, 16, 'utf-8');
            //17    ご依頼主名称２
            $data[17] = mb_substr($order['order_name'], 16, 16, 'utf-8');
            //18    荷姿コード
            $data[18] = '';
            if (empty($config['item_flg'])) {
                //19    品名１
                $data[19] = '';
                //20    品名２
                $data[20] = '';
                //21    品名３
                $data[21] = '';
                //22    品名４
                $data[22] = '';
                //23    品名５
                $data[23] = '';
                
            } else {
                //19    品名１
                $data[19] = '';
                //20    品名２
                $data[20] = '';
                //21    品名３
                $data[21] = '';
                //22    品名４
                $data[22] = '';
                //23    品名５
                $data[23] = '';
                
            }
            //24    出荷個数
            $data[24] = '';
            //25    便種(スピードで選択)
            $data[25] = '';
            //26    便種(商品)
            $data[26] = '';
            //27    配達日
            $data[27] = self::getShippingDate($order);
            //28    配達指定時間帯
            $data[28] = self::getDelivTime($order);
            //29    配達指定時間（時分）
            $data[29] = '';
            //30    代引金額
            $data[30] = self::getDaibiki($order);
            //31    消費税
            $data[31] = '';
            //32    決済種別
            $data[32] = '';
            //33    保険金額
            $data[33] = '';
            //34    保険金額印字
            $data[34] = '';
            //35    指定シール①
            $data[35] = empty($data[27])? '': '005';
            //36    指定シール②
            $data[36] = '';
            //37    指定シール③
            $data[37] = '';
            //38    営業店止め
            $data[38] = '';
            //39    ＳＲＣ区分
            $data[39] = '';
            //40    営業店コード
            $data[40] = '';
            //41    元着区分            
            $data[41] = '';            
            
            
            $arrData[] = $data;
        }
        
        return $objCSV->lfDownloadCsv($arrData, 'ehiden');
    }
    

    /**
     * 配達日を返す
     * @param array $order
     * @return string
     */
    function getShippingDate($order) {
        if (!empty($order['shipping_date'])) {
            $time = strtotime($order['shipping_date']);
            if ($time) {
                return date('Ymd', $time);
            }
        }
        return '';
    }
    
    
    
    function getDelivTime($order) {
        if (empty($order['shipping_time'])) {
            return '';
        }
        switch ($order['shipping_time']) {
            case '午前中':
                return '01';
            case '12:00～14:00':
                return '12';
            case '14:00～16:00':
                return '14';
            case '16:00～18:00':
                return '16';
            case '18:00～21:00':
                return '04';
            case '18:00～20:00':
                return '18';
            case '19:00～21:00 ':
                return '19';
        }
    }

    function getDaibiki($order) {
        if (preg_match('/代金引換/', $order['payment_method'])) {
            return $order['payment_total'];
        }
        return '';
    }
    
    
}

