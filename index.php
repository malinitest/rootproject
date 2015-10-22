#This is my source code
#Hi is this my new line
<?php
ini_set('error_reporting', 'E_ALL & ~E_NOTICE');
ini_set('display_errors', '1');
$gProgramCode = "INDEX";
include_once "scpt/utilities.inc";

if ($gDealerId > 0 && $gDealerId != $gDefaultDealerId) {
    include_once "scpt/class.shoppingcart.php";
    $cookieShoppingCartId = $_COOKIE["NFDNetwork"];
    if (empty($cookieShoppingCartId) || !is_numeric($cookieShoppingCartId)) {
        $itemsInCart = array();
    } else {
        $shoppingCart = new ShoppingCart($gDealerId);
        $shoppingCartId = $shoppingCart->getShoppingCart($cookieShoppingCartId);
        $itemsInCart = $shoppingCart->getShoppingCartItems();
    }
}
// POBF -108, Meta tags
$metaTagArray = array();
$query = "select * from dealer_meta_tags where dealer_id = ? ";
$resultSet = executeQuery($query, $gDealerId);
while ($row = getNextRow($resultSet)) {
    $metaTagArray['title'] = $row['title'];
    $metaTagArray['description'] = $row['description'];
    $metaTagArray['keyword'] = $row['keyword'];
    $metaTagArray['istitleinchild'] = $row['istitleinchild'];
    $metaTagArray['isdescriptioninchild'] = $row['isdescriptioninchild'];
    $metaTagArray['iskeywordinchild'] = $row['iskeywordinchild'];
}

$dealerArray = getDealerInfo($gDealerId);
$templateArray = getTemplateInfo($gDealerId, (empty($_GET['tmp']) ? "" : $_GET['tmp']));

$excludeArray = array();
$featureArray = array();

$featureDealerId = ($dealerArray['use_mall_features'] == 1 ? 1 : $gDealerId);
$query = "select ptl.product_id from product_tag_links ptl left join products p using (product_id) ";
$query .= "where ptl.dealer_id = ? and ptl.product_tag_id = 1 and p.internal_use_only = 0 and p.inactive = 0";
$checkSet = executeQuery("select control_list_item_id from dealer_control_lists where dealer_id = ? and internal_use_only = 1 and control_list_id = 3 and control_list_item_id = 155",$gDealerId);
if ($checkSet['row_count']> 0){               	
        $query .= " and p.manufacturer_id not in (155) ";
} 
$resultSet = executeQuery($query, $featureDealerId);
while ($row = getNextRow($resultSet)) {
    $featureArray[] = $row['product_id'];
}
shuffle($featureArray);
// add a product to the top of the array if dealer has set one
$resultSet = executeQuery("select * from product_tag_links where dealer_id = ? and product_tag_id = 1 and sort_order > 0", ($gDealerId > 1 ? $gDealerId : 1));
if ($row = getNextRow($resultSet)) {
    array_unshift($featureArray, $row['product_id']);
}

if (count($featureArray) > 0) {
    $rowArray = array();
    for ($i = 0; $i < count($featureArray); $i++) {
        $rowArray[] = $featureArray[$i];
        if (count($rowArray) == 3) {
            $excludeArray = array_merge($excludeArray, $rowArray);
            $rowArray = array();
        }
    }
}

// block items from exception list for this dealer's state?
if ($dealerArray['ignore_exceptions'] == 0) {
    $exceptionListBlock = getFieldFromId('exception_list_id', 'exception_lists', 'state', $dealerArray['dealer_state'], 'allowed = 0');
}

$leftSidebarLimit = ($gDealerId < 2 ? 6 : 12);
$rightSidebarLimit = ($gDealerId < 2 ? 19 : 23); // maybe have jQuery determine how many store badges to show?
// get this dealer's retail price preference
$preferenceId = getFieldFromId('preference_id', 'preferences', 'preference_code', 'USE_RETAIL_PRICE');
$resultSet = executeQuery("select * from dealer_preferences where preference_id = ? and dealer_id = ?", $preferenceId, $dealerId);
if ($row = getNextRow($resultSet)) {
    $useRetailPrice = ($row['preference_value'] == "Y" ? 1 : 0);
} else {
    $useRetailPrice = 0;
}
#EndUser Athentication checking

$urlAction = $_POST['action'];
switch ($urlAction) {
    case "login":
        $result = "";
        if (!empty($_POST['un'])) {
            $resultSet = executeQuery("select master_username,master_password from dealers where dealer_id = ? and master_username=?", $gDealerId,$_POST['un']);
            if ($row = getNextRow($resultSet)) {
                $user_name = $row['master_username'];
                if(!empty($_POST['pw'])){
                    if ($row['master_password'] == $_POST['pw']) {                     
                        globalLogin($row['master_username'],$gDealerId);                   
                    } else {
                        $result = "Password doesn't match";
                    }
                }
                else{
                    $result.= "Password can't be empty";
                }
            } else {
                $result = "Username is incorrect";
            }
        } else {
            $result.= "Username can't be empty";
        }
        break;
}
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <!-- POBF - 108, Meta tag update -->
        <title><?php echo (empty($metaTagArray['title']) ? $dealerArray['dealer_name'] : $metaTagArray['title']); ?></title>
        <meta name="description" content="<?php echo (empty($metaTagArray['description']) ? $dealerArray['dealer_name'] . " - " . $dealerArray['site_description'] : $metaTagArray['description']); ?>">
        <meta name="keywords" content="<?php echo (empty($metaTagArray['keyword']) ? $dealerArray['site_keywords'] : $metaTagArray['keyword']); ?>">        
        <!-- POBF - 108, Meta tag update -->
              
          
        <!-- Pinterest verification code -->
        <?php if (isset($dealerArray['pinterest']) && !empty($dealerArray['pinterest'])) {                      
            echo $dealerArray['pinterest'];
         }?>
        
        <link rel="stylesheet" href="templates/default/universal-styles-v3.css">
       <?php if ($dealerArray['enable_global_login'] == 1 ) {?>            
        <link rel="stylesheet" type="text/css" href="templates/default/global_login.css" />
       <?php }?>
        <link rel="stylesheet" href="<?php echo $templateArray['path'] ?>/styles-v1.css">
        <link rel="stylesheet" href="<?php echo $templateArray['homebanners_css'] ?>/homebanners.css">
        <link rel="stylesheet" href="scpt/custom-theme/jquery-ui.css">
        <?php if ($gDealerId == $gDefaultDealerId) { ?>
            <link rel="stylesheet" href="scpt/fancybox/jquery.fancybox.css">
            <link rel="stylesheet" href="<?php echo $templateArray['zipcodes'] ?>/zipcode_finder.css">
        <?php } ?>
        <style type="text/css">
            a{
                color: #0b7aae;
            }
            #login{
                margin-top: 122px;
            }
            input.primary, .button, .btnPrimary{
                background: #a11300;
                border: 1px solid #a11300;
            }          
            #login_footer, #login_footer a{
                color:#ffffff;
            }
            #content{
                background-color:#ffffff;
            }
            #content{
                border:1px solid #CCCCCC; 
                border-radius: 3px;-moz-border-radius:
                    3px;-webkit-border-radius: 3px; padding:10px;
            }
            #left_side {
                float:left; width: 320px;
            }
            #right_side{
                float:right;
                text-align:left;
                vertical-align:top;
                width: 480px;
                height: 330px;
                background: -ms-linear-gradient(top, #fafafa 0%, #e5e5e5 100%);
                background: -moz-linear-gradient(top, #fafafa 0%, #e5e5e5 100%);
                background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#fafafa), color-stop(100%,#e5e5e5));
            }
            #right_side h1{background-image: none;border: none}
        </style>
        <script type="text/javascript">
            if (top != self) {
                top.location.href = self.location.href;
            }
        </script>
        <script src="scpt/jquery.js"></script>
        <!--[if lt IE 9]>
        <script src="scpt/modernizr-2.0.6.js"></script>
        <![endif]-->
        <?php include_once "scpt/google_code.inc"; ?>
    </head>
    <body>     
        <?php
        #EndUser Authentication login form        
        if ($gDealerId != 1 && $dealerArray['enable_global_login'] == 1 && (!isset($_SESSION[$globalSystemCode]['global_user_id'])  || empty($_SESSION[$globalSystemCode]['global_user_id']))) {
        $globalImageId = getFieldFromId('preference_value', 'dealer_preferences', 'dealer_id', $gDealerId, 'preference_id = 1');  #1=>Dealer logo id
            ?>
            <div id="login">
                <div id="login_wrapper">
                    <div id="left_side">
                        <div id="login_component">
                            <div id="logo_wrapper">
                                <?php
                                if (!empty($globalImageId)) {
                                    $logoPath = '/imagedb/image' . $globalImageId . '-' . getImageHashCodeB($globalImageId);
                                    ?>						
                                    <a href="http://<?php echo $dealerArray['dealer_url']; ?>"><img id="logo" class = "logo_dealer" src="<?php echo $logoPath; ?>" alt="Dealer Logo" border="0"/></a>
                                    <?php
                                } else
                                    echo "Dealer Logo Not Available";
                                ?>
                            </div>
                            <div id="loginwidget">                            
                                <div id="loginformarea">                                                                     
                                    <div id="theloginform">
                                        <form name="login" method="post" action="index.php" target="_top" autocomplete="off" novalidate="novalidate">
                                            <input type="hidden" name="action" value="login" />
                                            <div class="loginError">
                                                <span><?php echo (empty($result) ? "" : $result); ?></span>
                                            </div>
                                            <div class="loginbox_container" onClick="document.login.username.focus();">
                                                
                                                <div class="identity first">
                                                    <label for="username" class="zen-assistiveText">User Name</label>
                                                    <span class="t">
                                                        <img id=loginthumb src="tmpl/user.png" alt="User Name" width="28" height="28" class="thumbnail" title="User Name" />
                                                    </span>
                                                    <input type="email" placeholder="User Name" value="<?php echo (isset($user_name)?$user_name:'')?>" class="input identityinput" id="username" name="un">
                                                </div>
                                            </div>
                                            <div class="loginbox_container" onClick="document.login.password.focus();">
                                                <div onClick="document.getElementById('password').value = '';this.style.display='none';document.login.pw.focus();" id="clrPw" class="clrField">&nbsp;</div>
                                                <div class="identity last">
                                                    <label for="password" class="zen-assistiveText">Password</label>
                                                    <span class="t">
                                                        <img src="tmpl/lock188.png" alt="Password" width="28" height="28" class="thumbnail" title="Password" />
                                                    </span>
                                                    <input type="password" placeholder="Password"class="input identityinput" id="password"name="pw"onkeypress="checkCaps(event)"autocomplete="off">
                                                </div>
                                            </div>
                                            <div id="pwcaps" class="loginbox_container" style="display:none">
                                                <img id="pwcapsicon" src="tmpl/warning.png" alt="Caps Lock is ON!" width="16" height="16" /> Caps Lock is ON!
                                            </div>
                                            <div class="loginbox_container">
                                                <button class="button" id="Login" name="Login">
                                                    <span class="label">Log into Dealer Site</span>
                                                </button>
                                            </div>																		
                                            <div class="loginbox_container" id="forgot">
                                                <span>
                                                    <a href="enduserauthforgot.php">Can't access account?</a>
                                                </span>																		
                                                &nbsp;&nbsp;&nbsp;&nbsp;
                                                <span class="wrapper_signup">
                                                    <a id="signup_link" href="/login.php">Dealer Login</a>
                                                </span>
                                            </div>
                                        </form>                                                
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>                
                    <div id="right_side" style="overflow-y:auto;">                    
                        <?php echo $htmlContent = getFieldFromId('global_login_content', 'dealers', 'dealer_id', $gDealerId); ?>
                    </div>
                </div>           
                <div id="login_footer">Copyright &copy; 2014 National Firearms Dealer Network. All rights reserved.</div>
            </div>
            <?php
        }else {
            if ($dealerArray['enable_global_login'] == 1 && (isset($_SESSION[$globalSystemCode]["global_user_id"]) && !empty($_SESSION[$globalSystemCode]["global_user_id"]))) {
                $globalUsername = getFieldFromId('session_data', 'sessions', 'session_id', $_SESSION[$globalSystemCode]["global_user_id"]);
                ?>
        <div id="welcomenote">
                <div class="wel-wrap">
                    <span class="welcome" style="vertical-align: middle; margin-right: 0px;color:#fff">Welcome, <?php echo $globalUsername; ?></span>                                       
                    <span style="color: #fff; class="sep">&nbsp;&nbsp;|&nbsp;&nbsp;</span>
                    <a id="gLogout" href="/globallogout.php" style="float:right;color:#fff;vertical-align: bottom; margin-right: 10px;">Logout</a>
                    </div>
                </div>
                <?php
            }
            include_once $templateArray['header'] . "/header.inc";
            include_once $templateArray['homebanners'] . "/homebanners.inc";
            include_once "scpt/catalog_functions.inc";
            ?>

            <!--<div class="spacer"></div>-->

            <table cellspacing="0" cellpadding="0"><tr>
                    <td valign="top">
                        <?php include_once $templateArray['left'] . "/sidebar.inc"; ?>
                    </td>
                    <td valign="top">

                        <table cellspacing="0" cellpadding="0"><tr>
                                <td valign="top">
                                    <div id="center_column"> 
                                        <?php if (count($featureArray) > 0) { ?>
                                            <div class="content">
                                                <div class="financeMessage"></div>
                                                <h1 class="featured_items">Featured Items</h1>
                                                <div class="big_feature">
                                                    <?php
                                                   $productId = $featureArray[0];

                                                    $productArray = getProductInfoMicro($productId, $gDealerId);
                                                    showBigFeature($productArray, $useRetailPrice);
                                                    ?> 
                                                </div>
                                                <?php
                                                $rowArray = array();
                                               for ($i = 1; $i < count($featureArray); $i++) {
                                                    $rowArray[] = $featureArray[$i];
                                                    if (count($rowArray) == 3) {
                                                        showCatalogRow($rowArray, $useRetailPrice);
                                                        $rowArray = array();
                                                    }
                                                    if ($i > 6) {
                                                        break;
                                                   }
                                                }  
                                                ?>
                                            </div>
                                            <div class='spacer'></div>

                                        <?php } ?>

                                        <?php if ($gDealerId == 1 || !empty($dealerArray['distributorSet'])) { ?>

                                            <div class="content">
                                                <h1 class="best_sellers">National Bestsellers</h1>
                                                <?php
                                                
                                                $start_date = date('Y-m-d', strtotime("-7 days"));
                                                $end_date = date('Y-m-d', strtotime("-1 day"));                                                
                                                
                                                $departmentArray = array(1, 2, 3, 9, 7);
                                                // check for any 'hidden' departments for this dealer
                                                $resultSet = executeQuery("select control_list_item_id from dealer_control_lists where dealer_id = ? and control_list_id = 1 and internal_use_only = 1", $gDealerId);
                                                while ($row = getNextRow($resultSet)) {
                                                    if (($key = array_search($row['control_list_item_id'], $departmentArray)) !== false) {
                                                        unset($departmentArray[$key]);
                                                    }
                                                }

                                                $queryKey = "Home_Best_Sellers_" . $gDealerId;

						$bestSellerProducts = array();

						$indexMemcache = false;

						if (class_exists(Memcache) && $memcache = new Memcache()) 
						{
						   foreach ($gMemcacheServers as $server) 
						   {
						      $memcache->addServer($server);
						   }
						
						   if ($dataStr = $memcache->get($queryKey)) 
						   {                                                        
						      $bestSellerProducts = json_decode($dataStr, true);                                                        
						      $indexMemcache  = true;
						   } 
						}

						if(!$indexMemcache)
						{
                                                   $query = "select p.product_id,p.category_id,avg(di.dealer_cost)*sum(dih.delta) as total_cost ";
                                                   $query .= "from products p left join distributor_inventory di using (product_id) ";
                                                   $query .= "left join distributor_inventory_history dih using (product_id,distributor_id) ";
                                                   $query .= "where di.quantity > 0 and dih.delta > 0 and dih.date_created between ? and ? ";
                                                   $query .= "and p.thumbnail_image_id is not null and p.internal_use_only = 0 and p.inactive = 0 and p.dealer_id is null ";
                                                   $queryParameters = array();
                                                   $queryParameters[] = $start_date;
                                                   $queryParameters[] = $end_date;

                                                   if (!empty($dealerArray['distributorSet'])) {
                                                       $query .= "and di.distributor_id in (" . $dealerArray['distributorSet'] . ") ";
                                                       $distributors = explode(",", $dealerArray['distributorSet']);
                                                       sort($distributors);
                                                   }
                                                   if (!empty($exceptionListBlock)) {
                                                       $query .= "and product_id not in (select product_id from exception_list_products where exception_list_id = ?) ";
                                                       $queryParameters[] = $exceptionListBlock;
                                                   }
                                                   $query .= "group by p.product_id order by total_cost desc";

                                                   $resultSet = executeQuery($query, $queryParameters);
                                                   $bestsellerArray = array();
                                                   while ($row = getNextRow($resultSet)) {
                                                        $bestsellerArray[$row['product_id']] = $row['category_id'];
                                                   }

                                                   // check for hidden departments/categories to exclude from bestsellers lists
                                                   $hiddenDepartments = array();
                                                   $hiddenCategories = array();
                                                   $hiddenManufacturerProducts = array();
                                                   $query = "select * from dealer_control_lists where dealer_id = ? and control_list_id = 1 and internal_use_only = 1";
                                                   $resultSet = executeQuery($query, $gDealerId);
                                                   while ($row = getNextRow($resultSet)) {
                                                       $hiddenDepartments[] = $row['control_list_item_id'];
                                                       $query = "select * from categories where department_id = ?";
                                                       $categorySet = executeQuery($query, $row['control_list_item_id']);
                                                       $catetoryRow = getNextRow($categorySet);
                                                       $hiddenCategories[] = $categoryRow['category_id'];
                                                   }
                                                   $query = "select * from dealer_control_lists where dealer_id = ? and control_list_id = 2 and internal_use_only = 1";
                                                   $resultSet = executeQuery($query, $gDealerId);
                                                   while ($row = getNextRow($resultSet)) {
                                                       if (!in_array($row['control_list_item_id'], $hiddenCategories)) {
                                                           $hiddenCategories[] = $row['control_list_item_id'];
                                                       }
                                                   }
                                                  
                                                    $checkSet = executeQuery("select product_id from products where manufacturer_id in (select control_list_item_id from dealer_control_lists where dealer_id = ? and internal_use_only = 1 and control_list_id = 3 and control_list_item_id = 155)",$gDealerId);
                                                    while ($row = getNextRow($checkSet)) {
                                                       if (!in_array($row['product_id'], $hiddenManufactuerProducts)) {
                                                           $hiddenManufacturerProducts[] = $row['product_id'];
                                                       }
                                                   }
    
                                                   $productCount = 3;

                                                   foreach ($departmentArray as $department_id) {
                                                       if (!in_array($department_id, $hiddenDepartments)) {
                                                           $categoryArray = array();
                                                           $category_result = executeQuery("select * from categories where department_id = ?", $department_id);
                                                           while ($category_row = getNextRow($category_result)) {
                                                               $categoryArray[] = $category_row['category_id'];
                                                           }
                                                           if (count($categoryArray) == 0) {
                                                               continue;
                                                           }
                                                           $productSet = array();
                                                           //$count = 0;
                                                           foreach ($bestsellerArray as $product_id => $category_id) {
                                                               if (in_array($category_id, $categoryArray) && !in_array($category_id, $hiddenCategories) && !in_array($product_id, $excludeArray) && !in_array($product_id, $hiddenManufacturerProducts)) {
                                                                   $productSet[] = $product_id;
                                                               } 
                                                               if (count($productSet) >= $productCount) {
                                                                   break;
                                                               }
                                                           }
                                                           if (count($productSet) == 3) {
							       foreach($productSet as $product_id)
							       {
							          $bestSellerProducts[] = $product_id;
							       }
                                                           }
                                                       }
                                                   }

						   if($memcache != null)
						   {
						      $memcache->set($queryKey, json_encode($bestSellerProducts), false, 43200);
						      $indexMemcache = true;
						   }
						}

						$productSet = array();
						foreach($bestSellerProducts as $product_id)
						{
						   $productSet[] = $product_id;

						   if(count($productSet) == 3)
						   {
						      showCatalogRow($productSet,$useRetailPrice);
						      $productSet = array();
						   }
						}

                                                ?>
                                            </div>
                                        <div class="financeMessage">
                                        </div>
                                        <div id="disclaimerContent"></div>
                                        <?php } ?>

                                    </div> <!-- id=center_column -->
                                </td>
                                <td valign="top">
                                    <?php include_once $templateArray['right'] . "/right.inc"; ?>
                                </td>
                            </tr></table>
                    </td>

                </tr></table>

            <div class='spacer' style='font-size: 10px; color: #333;'>
                <?php
                echo ($fromMemcache ? "." : "+");
                ?>
            </div>

            <?php include_once $templateArray['footer'] . "/footer.inc";      
            ?>
            <?php
            if ($gDealerId == $gDefaultDealerId) {
                include_once "scpt/zipcode_finder.inc";
            }
            ?>

            <script src="scpt/jquery-ui.js"></script>
            <script src="scpt/shared_v1.js"></script>
            <?php if ($gDealerId == $gDefaultDealerId) { ?>
                <script src="scpt/fancybox/jquery.fancybox.js"></script>
                <script src="scpt/zipcode_finder.js"></script>
            <?php } ?>
            <?php
            if (file_exists($templateArray['homebanners'] . "/home.js")) {
                $homeJSPath = $templateArray['homebanners'];
            } else {
                $homeJSPath = $templateArray['default'];
            }
            ?>
            <script src="/<?php echo $homeJSPath ?>/home.js"></script>
        <?php } #enduser login check?>
        <input type="hidden" id="site_dealer_id" value="<?php echo $GLOBALS['gDealerId'] ?>" />
        <input type="hidden" id="left_ad_block_margin" value="<?php echo $leftAdBlockMargin ?>" />
        <input type="hidden" id="right_ad_block_margin" value="<?php echo $rightAdBlockMargin ?>" />
        <script src="scpt/baselogin.js"></script>
        <input type="hidden" id="commonwealth_finance_enabled" value="<?php echo $dealerArray['enable_commonwealth'] == 1 ? 1 : 0 ?>" class="info">
        <script>
            $(window).load(function() {
                $("#sidebar").css('overflow', 'hidden');
                $("#sidebar").height($("#center_column").height());
                $.each($(".ad_link"), function(index) {
                    if ($(this).offset().top + $(this).height() > $("#center_column").offset().top + $("#center_column").height()) {
                        $(this).remove();
                    }
                });
                $("#sidebar_right").css('overflow', 'hidden');
                $("#sidebar_right").height($("#center_column").height());
                $.each($(".ad_space"), function(index) {
                    if ($(this).offset().top + $(this).height() > $("#center_column").offset().top + $("#center_column").height()) {
                        $(this).remove();
                    }
                });
                
                var Pare = $("#sidebar_right a img");
                $.each(Pare,function(){
                var val= /ad-link=([0-9]*)/.exec($(this).parent().attr('href'));
                console.log(val[1]);
                $.ajax({
                url: 'scpt/increment_ad_impression.php',
                type: "POST",
				data: {advertising_id: val[1]},
                dataType: "json"
                 });                  
                });
                
                var Par = $("#home_slides .home_slide");
                $.each(Par,function(){
                var valimp= /ad-link=([0-9]*)/.exec($(this).parent().attr('href'));
                $.ajax({
                url: 'scpt/increment_ad_impression.php',
                type: "POST",
				data: {advertising_id: valimp[1]},
                dataType: "json"
                 });                  
                });
                
               
                
            });
              //prevent back start
               if (location.hash == '#index'){ 
                    history.pushState(null, '', '#home');	
                    window.onhashchange = function() {
                            if (location.hash == '#index') {
                                    history.pushState(null, '', '#home');
                            }
                    };
                } 
                else if(location.hash == "#home"){
                        history.pushState(null, '', '#index'); 
                        window.onhashchange = function() { 
                                if (location.hash == '#home') { 
                                        history.pushState(null, '', '#index');
                                } 
                        }; 
                }  
                //prevent back end
        </script>

        <?php
        if (str_replace("www.", "", $_SERVER['HTTP_HOST']) == "gunsetc.com") {
            include_once "scpt/gunsetc_pixel_tracking.inc";
        }
        ?>
        <?php
        include_once "finance_faqs.htm";
        ?>
        <script> // For loading finance disclaimer content
            $(function(){             
             if($("#commonwealth_finance_enabled").val()==1)
              {
                $(".financeMessage").html('<img src="tmpl/SPECIALFINANCING1.png" />');
                $("#disclaimerContent").load("cw_disclaimer.htm");
             }
            });
        </script>
    </body>
</html>
