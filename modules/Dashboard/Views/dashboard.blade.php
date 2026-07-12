<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous">
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.13.1/font/bootstrap-icons.min.css"
        integrity="sha512-t7Few9xlddEmgd3oKZQahkNI4dS6l80+eGEzFQiqtyVYdvcSG2D3Iub77R20BdotfRPA9caaRkg1tyaJiPmO0g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/fuse.js/7.1.0/fuse.min.js"
        integrity="sha512-H1bWCnc4dDJwdioqpOCkU76ZxEdvBvOy9R9Dd9EqftlzQg92owjX5IVdiOw00llAyQFUZJNPrzDnWZ/lZtf25A=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <link
        href="https://fonts.googleapis.com/css2?family=Playfair:ital,opsz,wght@0,5..1200,300..900;1,5..1200,300..900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <meta name="csrf-token" content="{{ csrf_token() }}"> 

    @vite([
        'resources/css/style.css',
        'resources/js/app.js'
    ])


</head>

<body>



    <div class="main-wrapper">
        <div class="sidebar d-flex flex-column" style="height: 100vh; background-color: #000000;">
            <!-- Main Menu Items -->
            <ul class="sidebar-menu flex-grow-1 p-0 m-0">
                <li class="menu-item {{ request()->is('dashboard') ? 'active' : '' }}">
                    <a href="/dashboard" class="text-white text-decoration-none">
                        <div class="menu-title">
                            <i class="bi bi-diagram-2"></i>&nbsp;Dashboard
                        </div>
                    </a>
                </li>

                <li class="menu-item {{ request()->is('product/catalog') || request()->is('units') || request()->is('locations') || request()->is('grades') ? 'active' : '' }}">
                    <div class="menu-title">
                        <i class="bi bi-box-seam"></i>&nbsp; Units & Locations
                        <i class="bi bi-caret-down"></i>
                    </div>
                    <ul class="submenu">
                        <li><a href="/units"><i class="bi bi-rulers"></i> Units</a></li>
                        <li><a href="/locations"><i class="bi bi-geo-alt"></i> Locations</a></li>
                        <li><a href="/grades"><i class="bi bi-award"></i>Product Grades</a></li>
                        <li><a href="/product/catalog"><i class="bi bi-box-seam"></i> Product Catalog</a></li>
                        
                    </ul>
                </li>

                <li class="menu-item {{ request()->is('stock-movements') || request()->is('stock-transfer') || request()->is('stock-segregation') ? 'active' : '' }}">
                    <div class="menu-title">
                        <i class="bi bi-box-seam"></i>&nbsp;Stock Management
                        <i class="bi bi-caret-down"></i>
                    </div>
                    <ul class="submenu">
                        <li><a href="/stock-movements"><i class="bi bi-arrow-down-up"></i> Stock In/Out</a></li>
                        <li><a href="/stock-transfer"><i class="bi bi-arrow-left-right"></i> Internal Transfer</a></li>
                        <li><a href="/stock-segregation"><i class="bi bi-diagram-3"></i> Stock Segregation</a></li>
                    </ul>
                </li>

                <li class="menu-item {{ request()->is('fleet-routes') || request()->is('fleet-vehicles') || request()->is('fleet-trips') || request()->is('fleet/sale') ? 'active' : '' }}">
                    <div class="menu-title">
                        <i class="bi bi-truck"></i>&nbsp;Fleet Management
                        <i class="bi bi-caret-down"></i>
                    </div>
                    <ul class="submenu">
                        <li><a href="/fleet-routes"><i class="bi bi-signpost-split"></i> Routes</a></li>
                        <li><a href="/fleet-vehicles"><i class="bi bi-truck"></i> Vehicles</a></li>
                        <li><a href="/fleet-trips"><i class="bi bi-calendar2-week"></i> Fleet Trips</a></li>
                        <li><a href="/fleet/sale"><i class="bi bi-receipt"></i> Fleet Sales</a></li>
                        <li><a href="#" data-bs-toggle="modal" data-bs-target="#uploadReportModal"><i class="bi bi-file-earmark-arrow-up"></i> Upload Sale Report</a></li>
                    </ul>
                </li>

                <li class="menu-item {{ request()->is('shop/sale') || request()->is('shop/overview') ? 'active' : '' }}">
                    <div class="menu-title">
                        <i class="bi bi-shop"></i>&nbsp;Shop
                        <i class="bi bi-caret-down"></i>
                    </div>
                    <ul class="submenu">
                        <li><a href="/shop/sale"><i class="bi bi-receipt"></i> Sales</a></li>
                        <li><a href="/shop/overview"><i class="bi bi-speedometer2"></i> Overview</a></li>
                    </ul>
                </li>

                <li class="menu-item {{ request()->is('warehouse/sale') || request()->is('warehouse/overview') || request()->is('warehouse/credits') ? 'active' : '' }}">
                    <div class="menu-title">
                        <i class="bi bi-building"></i>&nbsp;Warehouse
                        <i class="bi bi-caret-down"></i>
                    </div>
                    <ul class="submenu">
                        <li><a href="/warehouse/sale"><i class="bi bi-cart-plus"></i> Sales</a></li>
                        <li><a href="/warehouse/overview"><i class="bi bi-speedometer2"></i> Overview</a></li>
                        <li><a href="/warehouse/credits"><i class="bi bi-credit-card"></i> Credit Details</a></li>
                    </ul>
                </li>

                <li class="menu-item {{ request()->is('expenses') ? 'active' : '' }}">
                    <div class="menu-title">
                        <i class="bi bi-wallet2"></i>&nbsp;Expenses
                        <i class="bi bi-caret-down"></i>
                    </div>
                    <ul class="submenu">
                        <li><a href="/expenses"><i class="bi bi-wallet2"></i> Expense List</a></li>
                    </ul>
                </li>



                <li class="menu-item {{ request()->is('reports/*') ? 'active' : '' }}">
                    <div class="menu-title">
                        <i class="bi bi-shop"></i>&nbsp;Reports
                        <i class="bi bi-caret-down"></i>
                    </div>
                    <ul class="submenu">
                        <li><a href="#"><i class="bi bi-file-earmark-bar-graph"></i> Report - Shop</a></li>
                        <li><a href="#"><i class="bi bi-file-earmark-bar-graph"></i> Report - Warehouse</a></li>
                        <li><a href="#"><i class="bi bi-file-earmark-bar-graph"></i> Report - Fleet</a></li>
                        <li><a href="#"><i class="bi bi-file-earmark-bar-graph"></i> Report - Quarterly</a></li>
                        <li><a href="#"><i class="bi bi-file-earmark-bar-graph"></i> Report - Credits</a></li>
                    </ul>
                </li>
            </ul>

            <!-- Bottom Menu Items -->
            <div class="mt-auto bg-grey">
                <ul class="sidebar-menu p-0 m-0">
                    <li class="menu-item" style="font-size: 0.72em;">
                        <a href="" class="text-white text-decoration-none d-flex align-items-center px-3 py-2">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

        </div>

        <!-- Optional: Sidebar Toggle Script -->




        <div class="dynamic">
          






            @yield('product_catalog')

            @yield('units')

            @yield('locations')

            @yield('grades')


            @yield('stock_transfer')

            @yield('stock_transit')

            @yield('stock_overview')


            @yield('fleets')
            @yield('fleets_routes')
            @yield('fleet_vehicles')
            @yield('fleet_trip')
            @yield('fleet_sale')


            @yield('shop_sale')

            @yield('shop_overview')

            @yield('warehouse_sale')

            @yield('warehouse_overview')

            @yield('expense')



            @yield('content')

            @yield('stock_in')
















        </div>

    <!-- Upload Fleet Sale Report Modal -->
    <div class="modal fade" id="uploadReportModal" tabindex="-1" aria-labelledby="uploadReportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="uploadReportForm" enctype="multipart/form-data">
                    <div class="modal-header" style="background-color: #f1f5f1ff;">
                        <h5 class="modal-title" id="uploadReportModalLabel">Upload Fleet Sale Report <i class="bi bi-file-earmark-arrow-up"></i></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="report_trip_id" class="form-label">Select Trip (Latest 5)</label>
                            <select id="report_trip_id" name="trip_id" class="form-select" required>
                                <option value="" disabled selected>Loading trips...</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="report_file" class="form-label">Select Excel File (.xlsx, .xls)</label>
                            <input class="form-control" type="file" id="report_file" name="report_file" accept=".xlsx, .xls" required>
                        </div>
                        <div id="upload-error-message" class="text-danger text-small mt-2" style="display: none;"></div>
                        <div id="upload-success-message" class="text-success text-small mt-2" style="display: none;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success" id="btn-upload-report">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @vite(['resources/js/fleet/upload_report.js'])
</body>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuTitles = document.querySelectorAll('.menu-title');

        menuTitles.forEach(title => {
            title.addEventListener('click', () => {
                const parent = title.parentElement;

                // optional: close others when one opens
                document.querySelectorAll('.menu-item').forEach(item => {
                    if (item !== parent) item.classList.remove('active');
                });

                // toggle clicked section
                parent.classList.toggle('active');
            });
        });
    });
</script>


</html>
