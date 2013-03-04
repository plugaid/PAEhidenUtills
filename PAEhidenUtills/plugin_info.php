<?php
/*
 * PaBDaShop
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
 * プラグイン の情報クラス.
 *
 * @version $Id: plugin_info.php 94 2013-03-04 06:14:16Z t.takabayashi $
 */
class plugin_info{
    /** プラグインコード(必須)：プラグインを識別する為キーで、他のプラグインと重複しない一意な値である必要がありま. */
    static $PLUGIN_CODE       = "PAEhidenUtills";
    /** プラグイン名(必須)：EC-CUBE上で表示されるプラグイン名. */
    static $PLUGIN_NAME       = "佐川急便e飛伝Ⅱユーティリティープラグイン";
    /** プラグインバージョン(必須)：プラグインのバージョン. */
    static $PLUGIN_VERSION    = "0.1.0";
    /** 対応バージョン(必須)：対応するEC-CUBEバージョン. */
    static $COMPLIANT_VERSION = "2.12.3";
    /** 作者(必須)：プラグイン作者. */
    static $AUTHOR            = "株式会社プラグエイド";
    /** 説明(必須)：プラグインの説明. */
    static $DESCRIPTION       = "佐川急便e飛伝Ⅱ用のCSVのインポート・エクスポート処理を行うことが可能です";
    /** プラグインURL：プラグイン毎に設定出来るURL（説明ページなど） */
    static $PLUGIN_SITE_URL   = "http://plug-aid.jp/";
    /** プラグイン作者URL：プラグイン毎に設定出来るURL（説明ページなど） */
    static $AUTHOR_SITE_URL   = "http://plug-aid.jp/";
    /** クラス名(必須)：プラグインのクラス（拡張子は含まない） */
    static $CLASS_NAME       = "PAEhidenUtills";
    /** フックポイント：フックポイントとコールバック関数を定義します */
    static $HOOK_POINTS       = array(
		array("LC_Page_Admin_Order_action_after", 'afterActionAdminOrder'),
//        array("LC_Page_Products_Detail_action_before", 'beforeActionProductsDetail'),
//        array("LC_Page_Products_Detail_action_before", 'afterActionProductsDetail'),
//        array("LC_Page_Admin_Products_Category_action_after", 'contents_set'),
//        array("LC_Page_Products_List_action_after", 'disp_contents'),
        array("prefilterTransform", 'prefilterTransform')
	);
    /** ライセンス */
    static $LICENSE        = "LGPL";
}
?>