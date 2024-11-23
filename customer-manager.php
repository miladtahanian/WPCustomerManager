<?php
/*
Plugin Name: Another Customer Manager
Description: A more visually appealing customer manager with modal to view customer details and notes.
Version: 1.0
Author: Milad Tahanian
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Activation: Create or update the custom database tables
register_activation_hook( __FILE__, 'customer_manager_install' );
function customer_manager_install() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Customers table
    $table_name = $wpdb->prefix . "customers";
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        phone varchar(15) NOT NULL,
        codemelli varchar(10) NOT NULL,
        birthdate text NOT NULL,
        seller tinytext NOT NULL,
        address text NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Notes table
    $notes_table = $wpdb->prefix . "customer_notes";
    $sql_notes = "CREATE TABLE $notes_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        customer_id mediumint(9) NOT NULL,
        note text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    dbDelta( $sql_notes );
}

// Admin Menu
add_action( 'admin_menu', 'customer_manager_menu' );
function customer_manager_menu() {
    add_menu_page( 'Customer Manager', 'باشگاه مدیریت مشتریان', 'edit_pages', 'customer-manager', 'customer_manager_page', 'dashicons-businessman' );
}

// Plugin Page in Admin Panel
function customer_manager_page() {
    global $wpdb;
    $customers_table = $wpdb->prefix . "customers";
    $notes_table = $wpdb->prefix . "customer_notes";

    // Pagination variables
    $customers_per_page = 10;
    $current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
    $offset = ( $current_page - 1 ) * $customers_per_page;

    // Handle form submission to add a customer
    if ( isset( $_POST['submit'] ) ) {
        // Check if the form data is valid
        if ( !empty( $_POST['name'] ) && !empty( $_POST['phone'] ) ) {
            $wpdb->insert(
                $customers_table,
                [
                    'name'      => sanitize_text_field( $_POST['name'] ),
                    'phone'     => sanitize_text_field( $_POST['phone'] ),
                    'codemelli' => sanitize_text_field( $_POST['codemelli'] ),
                    'birthdate' => sanitize_text_field( $_POST['birthdate'] ),
                    'seller' => sanitize_text_field( $_POST['seller'] ),
                    'address'   => sanitize_textarea_field( $_POST['address'] )
                ]
            );
            echo "<div class='updated'><p>مشتری اضافه شد!</p></div>";
        }
    }

    // Handle note submission
    if ( isset( $_POST['add_note'] ) && isset( $_POST['customer_id'] ) ) {
        $wpdb->insert(
            $notes_table,
            [
                'customer_id' => intval( $_POST['customer_id'] ),
                'note'        => sanitize_textarea_field( $_POST['note'] )
            ]
        );
        echo "<div class='updated'><p>یادداشت با موفقیت ذخیره شد!</p></div>";
    }

    // Handle deletion of customer
    if ( isset( $_GET['delete'] ) ) {
        $wpdb->delete( $customers_table, [ 'id' => intval( $_GET['delete'] ) ] );
        $wpdb->delete( $notes_table, [ 'customer_id' => intval( $_GET['delete'] ) ] );
        echo "<div class='updated'><p>مشتری به همراه یادداشت های آن حذف شد.</p></div>";
    }

    // Handle search functionality
    $search_query = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

    // Fetch total customers
    $total_customers = $wpdb->get_var( "SELECT COUNT(*) FROM $customers_table WHERE name LIKE '%$search_query%'" );

    // Fetch customers with pagination
    $query = "SELECT * FROM $customers_table WHERE name LIKE '%$search_query%' LIMIT $offset, $customers_per_page";
    $customers = $wpdb->get_results( $query );

    // Display form and customer table
    ?>

    
    <div class="customer-manager">
    <div class="vl"></div>
        <h1 class="page-title">سامانه مدیریت مشتریان</h1>

        <!-- Add Customer Modal -->
        <div id="add-modal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="document.getElementById('add-modal').style.display='none'">&times;</span>
                <form method="post" action="">
                    <h2>افزودن مشتری</h2>
                    <input type="text" name="name" placeholder="نام" required><br/><br/>
                    <input type="text" name="phone" placeholder="شماره تماس" required><br/><br/>
                    <input type="text" name="codemelli" placeholder="کد ملی" maxlength="10" required><br/><br/>
                    <input type="text" name="birthdate" placeholder="تاریخ تولد" required><br/><br/>
                    <input type="text" name="seller" placeholder="نام فروشنده" required><br/><br/>
                    <textarea name="address" placeholder="آدرس" required></textarea><br/>
                    <input type="submit" name="submit" value="ذخیره" class="button button-primary"><br/>
                </form>
                <!-- Close Button for Add Customer Modal -->
                <button class="button button-secondary close-modal" onclick="document.getElementById('add-modal').style.display='none'">
                    بازگشت
                </button>
            </div>
        </div>

        <!-- Search Form -->
        <div class="search-container">
            <form method="get">
                <input type="hidden" name="page" value="customer-manager">
                <input type="text" name="s" placeholder="جستجوی مشتریان" value="<?php echo esc_attr( $search_query ); ?>">
                <button type="submit" class="button">جستجو</button>
            </form>
        </div>

        <div class="add-customer-button">
            <!-- Add Customer Button -->
            <button class="button add-customer" onclick="document.getElementById('add-modal').style.display='block'">
            <span class="dashicons dashicons-plus-alt"></span> افزودن مشتری
        </button>
        </div>

        <!-- Customer Table -->
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th>نام</th>
                    <th>شماره تماس</th>
                    <th>کد ملی</th>
                    <th>تاریخ تولد</th>
                    <th>فروشنده</th>
                    <th>آدرس</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $customers ) : ?>
                    <?php foreach ( $customers as $customer ) : ?>
                        <tr>
                            <td><?php echo esc_html( $customer->name ); ?></td>
                            <td><?php echo esc_html( $customer->phone ); ?></td>
                            <td><?php echo esc_html( $customer->codemelli ); ?></td>
                            <td><?php echo esc_html( $customer->birthdate ); ?></td>
                            <td><?php echo esc_html( $customer->seller ); ?></td>
                            <td><?php echo esc_html( $customer->address ); ?></td>
                            <td>
                                <button class="button button-primary" onclick="viewCustomer(<?php echo esc_js( $customer->id ); ?>)">
                                    <span class="dashicons dashicons-visibility"></span> یادداشت ها
                                </button>
                                <!-- <a class="button button-secondary" href="?page=customer-manager&delete=<?php echo intval( $customer->id ); ?>" onclick="return confirm('آیا میخواهید مشتری و تمام یادداشت های آن حذف شود؟');">
                                    <span class="dashicons dashicons-trash"></span> حذف
                                </a> -->
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="7">مشتری پیدا نشد</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <?php
            $total_pages = ceil( $total_customers / $customers_per_page );
            for ( $i = 1; $i <= $total_pages; $i++ ) {
                $active = $i == $current_page ? 'style="background:#0073aa;color:#fff;"' : '';
                echo '<a ' . $active . ' href="?page=customer-manager&paged=' . $i . '">' . $i . '</a>';
            }
            ?>
        </div>

        <!-- Modal for Viewing Customer Details and Notes -->
        <div id="view-modal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="document.getElementById('view-modal').style.display='none'">&times;</span>
                <div id="modal-details"></div>
            </div>
        </div>
    </div>

    <div class="calculator-container" style="margin-top: 100px;">
    <div class="vl"></div>
        <h1>محاسبه شرایط اقساط</h1>
        <div id="calculator">
            <input type="number" id="calc-price" placeholder="قیمت" style="margin-right: 10px; padding: 5px; text-align:right;" />
            <label for="calc-price">قیمت وارد کنید</label>
            <div id="calc-results" style="margin-top: 15px; font-size: 14px; font-weight: bold; padding:20px"></div>
        </div>
    </div>

    <script>
        function viewCustomer(customerId) {
            // Fetch customer details and notes
            fetch('<?php echo admin_url( "admin-ajax.php" ); ?>?action=get_customer_details&customer_id=' + customerId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('modal-details').innerHTML = data.html;
                        document.getElementById('view-modal').style.display = 'block';
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
        }

        // Close modals if clicked outside
        window.onclick = function(event) {
            if (event.target === document.getElementById('view-modal')) {
                document.getElementById('view-modal').style.display = 'none';
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            const calcInput = document.getElementById("calc-price");
            const calcResults = document.getElementById("calc-results");

            calcInput.addEventListener("input", function() {
                const price = parseInt(calcInput.value);
                if (isNaN(price)) {
                    calcResults.innerHTML = "لطفا عدد معتبری وارد کنید.";
                    return;
                }

                // Perform example calculations
                const davazdahoNim = (12.5 * price) / 100;
                const bistoPanj = (25 * price) / 100;
                const tenMonthPrice = parseInt(price) + parseInt(davazdahoNim);
                const twentyMonthPrice = parseInt(price) + parseInt(bistoPanj);
                const pishTenMonth = (15 * tenMonthPrice) / 100;
                const ghestTenMonth = parseInt(tenMonthPrice - pishTenMonth) / 10;
                const pishTwentyMonth = (20 * twentyMonthPrice) / 100;
                const ghestTwentyMonth = parseInt(twentyMonthPrice - pishTwentyMonth) / 10;
                const pishThirtySixMonth = (36 * twentyMonthPrice) / 100;
                const ghestThirtySixMonth = parseInt(twentyMonthPrice - pishThirtySixMonth) / 10;
                

                calcResults.innerHTML = `
                        <div style="border:1px solid black;border-radius:10px;padding:10px;margin:5px">
                            <p>قیمت نهایی 10 ماهه:<br/>${tenMonthPrice}</p>
                            <br />
                            <p>پیش پرداخت 10 ماهه:<br/>${pishTenMonth}</p>
                            <br />
                            <p>مبلغ هر قسط:<br/>${ghestTenMonth}</p>
                            <br />
                        </div>
                        <div style="border:1px solid black;border-radius:10px;padding:10px;margin:5px">
                            <p>قیمت نهایی 20 ماهه:<br/>${twentyMonthPrice}</p>
                            <br />
                            <p>پیش پرداخت 20 ماهه:<br/>${pishTwentyMonth}</p>
                            <br />
                            <p>مبلغ هر قسط:<br/>${ghestTwentyMonth}</p>
                            <br />
                        </div>
                        <div style="border:1px solid black;border-radius:10px;padding:10px;margin:5px">
                            <p>قیمت نهایی 36 ماهه:<br/>${twentyMonthPrice}</p>
                            <br />
                            <p>پیش پرداخت 36 ماهه:<br/>${pishThirtySixMonth}</p>
                            <br />
                            <p>مبلغ هر قسط:<br/>${ghestThirtySixMonth}</p>
                            <br />
                        </div>
                `;
            });
        });
    </script>

    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            padding-top: 50px;
        }
        .modal-content {
            background-color: #fff;
            margin: auto;
            padding: 20px;
            width: 80%;
            max-width: 800px;
        }
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            right: 20px;
            top: 20px;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        .search-container {
            margin-bottom: 20px;
            flex-direction: row
        }
        .wp-list-table th {
            text-align: right;
        }
        .wp-list-table {
            text-align: right
        }
        .pagination a {
            padding: 5px 10px;
            margin: 0 5px;
            text-decoration: none;
            background-color: #0073aa;
            color: white;
            border-radius: 5px;
        }
        .pagination a:hover {
            background-color: #005177;
        }
        .pagination {
            margin-top:10px
        }
        .close-modal {
            margin-top: 15px;
            padding: 10px 20px;
            background-color: #f44336; /* Red color */
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .close-modal:hover {
            background-color: #d32f2f; /* Darker red on hover */
        }
        .add-customer-button {
            margin-bottom: 15px
        }
        .h1,h2,h3 {
            text-align: right
        }
        p {
            text-align: right
        }
        li {
            text-align: right
        }
        .dashicons {
            margin-top: 5px
        }
        input {
            margin: 10px;
            padding: 5px;
        }
        textarea {
            margin-left:10px;
            width: 175px;
            padding: 5px;
        }
        .vl {
  border-top: 2px solid black;
  width: 500px;
  height:10px
}
        .wp-list-table.widefat.striped {
    width: 100%;
    overflow-x: auto; /* Add horizontal scrolling for larger tables */
}

@media only screen and (max-width: 768px) {
    .wp-list-table.widefat.striped {
        /* Adjust table layout for smaller screens */
        display: block;
        width: 100%;
    }

    .wp-list-table.widefat.striped th,
    .wp-list-table.widefat.striped td {
        display: block;
        width: 100%;
    }

    .wp-list-table.widefat.striped th {
        text-align: left;
        padding-bottom: 5px;
    }

    .wp-list-table.widefat.striped td {
        text-align: right;
        padding-bottom: 10px;
    }

    .wp-list-table.widefat.striped td:before {
        content: attr(data-th) ": ";
        font-weight: bold;
        display: inline-block;
        width: 150px; /* Adjust width as needed */
    }
    textarea {
        width:275px
    }
    input {
        width:275px
    }
}
    </style>

<?php
}

// AJAX handler to fetch customer details and notes
add_action('wp_ajax_get_customer_details', 'get_customer_details');
function get_customer_details() {
    global $wpdb;

    $customer_id = intval($_GET['customer_id']);
    $customers_table = $wpdb->prefix . "customers";
    $notes_table = $wpdb->prefix . "customer_notes";

    $customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $customers_table WHERE id = %d", $customer_id));
    if (!$customer) {
        wp_send_json(['success' => false, 'message' => 'مشتری پیدا نشد']);
    }

    $notes = $wpdb->get_results($wpdb->prepare("SELECT * FROM $notes_table WHERE customer_id = %d ORDER BY created_at DESC", $customer_id));

    ob_start();
    ?>
    <h2>جزئیات مربوط به <?php echo esc_html($customer->name); ?></h2>
    <p><strong>شماره تماس:</strong> <?php echo esc_html($customer->phone); ?></p>
    <p><strong>کد ملی:</strong> <?php echo esc_html($customer->codemelli); ?></p>
    <p><strong>تاریخ تولد:</strong> <?php echo esc_html($customer->birthdate); ?></p>
    <p><strong>فروشنده:</strong> <?php echo esc_html($customer->seller); ?></p>
    <p><strong>آدرس:</strong> <?php echo esc_html($customer->address); ?></p>

    <h3>یادداشت ها</h3>
    <ul>
        <?php if ($notes) : ?>
            <?php foreach ($notes as $note) : ?>
                <li>
                    <strong><?php echo date_i18n('Y-m-d H:i:s', strtotime($note->created_at)); ?>:</strong><br/>
                    <?php echo esc_html($note->note); ?>
                </li>
            <?php endforeach; ?>
        <?php else : ?>
            <li>یادداشتی اضافه نشده است</li>
        <?php endif; ?>
    </ul>

    <h3>افزودن یادداشت</h3>
    <form method="post" action="">
        <textarea name="note" placeholder="متن یادداشت را اینجا بنویسید" required></textarea><br>
        <input type="hidden" name="customer_id" value="<?php echo esc_attr($customer->id); ?>">
        <input type="submit" name="add_note" value="ذخیره یادداشت" class="button button-primary">
    </form>
    <?php
    $html = ob_get_clean();

    wp_send_json(['success' => true, 'html' => $html]);
}
?>
<?php

// Create a Shortcode for Frontend Display
function customer_manager_shortcode( $atts ) {
    ob_start();
    customer_manager_page(); // This calls the function that renders the customer manager page.
    return ob_get_clean();
}
add_shortcode( 'customer_manager', 'customer_manager_shortcode' );

?>
