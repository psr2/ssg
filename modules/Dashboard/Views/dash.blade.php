@extends('dashboard::dashboard')

@section('content')
  <div class="dashboard-title ms-3 pb-3">
        <div>
            <i class="bi bi-diagram-2"></i>&nbsp;Dashboard | SG & Associates , Theni
            <hr style="color: grey;">
        </div>
  </div>
    <div>
        <div class="row g-3 ms-2 ">
            <!-- Warehouse Stock -->
            <div class="col-md-4">
                <div class="card shadow-sm h-100 d-flex flex-column" style="background-color: #cce5ff;">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <div class="d-flex gap-2 w-100 px-3 mb-2">
                            <select class="form-select form-select-sm bg-transparent text-black border-white flex-fill" id="wh-stock-select">
                                <option value="all">All Warehouses</option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                @endforeach
                            </select>
                            <select class="form-select form-select-sm bg-transparent text-black border-white flex-fill" id="wh-product-select">
                                <option value="all">All Products</option>
                                @foreach ($productList as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        @php
                            if ($warehouse >= 1000) {
                                $displayValue = number_format($warehouse / 1000, 2) . ' T';
                            } else {
                                $displayValue = $warehouse . ' kg';
                            }
                        @endphp

                        <h3 class="fw-bold p-2" id="wh-stock-value">
                            <i class="bi bi-box-seam me-1"></i> {{ $displayValue }}
                        </h3>
                        <small class="text-muted">Stock in Warehouse</small>
                    </div>
                </div>
            </div>

            <!-- Shop Stock -->
            <div class="col-md-4">
                <div class="card shadow-sm h-100 d-flex flex-column" style="background-color: #e6f2ff;">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <div class="d-flex gap-2 w-100 px-3 mb-2">
                            <select class="form-select form-select-sm bg-transparent text-black border-white flex-fill" id="shop-stock-select">
                                <option value="all">All Shops</option>
                                @foreach ($shops as $sh)
                                    <option value="{{ $sh->id }}">{{ $sh->name }}</option>
                                @endforeach
                            </select>
                            <select class="form-select form-select-sm bg-transparent text-black border-white flex-fill" id="shop-product-select">
                                <option value="all">All Products</option>
                                @foreach ($productList as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        @php
                            if ($shopStock >= 1000) {
                                $shopDisplay = number_format($shopStock / 1000, 2) . ' T';
                            } else {
                                $shopDisplay = $shopStock . ' kg';
                            }
                        @endphp

                        <h3 class="fw-bold p-2" id="shop-stock-value">
                            <i class="bi bi-shop me-1"></i> {{ $shopDisplay }}
                        </h3>

                        <small class="text-muted ">Stock in Shop</small>
                    </div>
                </div>
            </div>

            <!-- Pending Money -->
            <div class="col-md-4">
                <div class="card shadow-sm h-100 d-flex flex-column" style="background-color: #d0ebff;">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <select class="form-select form-select-sm bg-transparent text-black border-white w-75 mb-2" id="receivables-select">
                            <option value="{{ $totalReceivables }}">All Locations</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $warehouseDues[$wh->id] ?? 0 }}">{{ $wh->name }} (Warehouse)</option>
                            @endforeach
                            @foreach ($shops as $sh)
                                <option value="{{ $shopDues[$sh->id] ?? 0 }}">{{ $sh->name }} (Shop)</option>
                            @endforeach
                        </select>

                        <h3 class="fw-bold p-2" id="receivables-value">
                            <i class="bi bi-currency-rupee me-1"></i>{{ number_format($totalReceivables, 2) }}
                        </h3>

                        <small class="text-muted">Total Receivables</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= Stock Alert ================= -->
        <div class="card mt-5 ms-3">
            <div class="card-header p-3 d-flex justify-content-between align-items-center" style="background-color: #ffffff; color: #000000; font-weight: bold;">
                <span><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> Stock Alert</span>
                <div style="width: 300px;">
                    <input type="text" id="product-search" class="form-control form-control-sm" placeholder="Search product, SKU, or location...">
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover mb-0" id="product-stock-table">
                    <thead style="text-align: center;">
                        <tr>
                            <th style="background-color: #f3f5f3d3; color: rgba(0, 0, 0, 0.87); font-weight:500;">#</th>
                            <th style="background-color: #f3f5f3d3; color: rgba(0, 0, 0, 0.87); font-weight:500; text-align: left; padding-left: 20px;">Product Name</th>
                            <th style="background-color: #f3f5f3d3; color: rgba(0, 0, 0, 0.87); font-weight:500;">Location</th>
                            <th style="background-color: #f3f5f3d3; color: rgba(0, 0, 0, 0.87); font-weight:500;">Type</th>
                            <th style="background-color: #f3f5f3d3; color: rgba(0, 0, 0, 0.87); font-weight:500;">Quantity</th>
                            <th style="background-color: #f3f5f3d3; color: rgba(0, 0, 0, 0.87); font-weight:500;">Purchase Date</th>
                            <th style="background-color: #f3f5f3d3; color: rgba(0, 0, 0, 0.87); font-weight:500;">Percentage of Current Holding</th>
                        </tr>
                    </thead>
                    <tbody style="text-align: center; font-weight:300;">
                        @forelse ($stockAlerts as $index => $alert)
                            <tr class="product-row" data-name="{{ strtolower($alert['product_name']) }}" data-sku="{{ strtolower($alert['sku'] ?? '') }}" data-location="{{ strtolower($alert['location_name']) }}">
                                <td>{{ $index + 1 }}</td>
                                <td style="text-align: left; padding-left: 20px; font-weight: 500;">{{ $alert['product_name'] }}</td>
                                <td>{{ $alert['location_name'] }}</td>
                                <td><span class="badge bg-secondary-subtle text-secondary-emphasis">{{ $alert['location_type'] }}</span></td>
                                <td class="fw-medium">{{ number_format($alert['current_qty'], 1) }} {{ $alert['unit'] }}</td>
                                <td>{{ $alert['received_date'] }}</td>
                                <td>
                                    <span class="badge {{ $alert['badge_class'] }}">{{ $alert['status'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted py-4">No stock alert records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        @keyframes blinkWarning {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.35;
            }

            100% {
                opacity: 1;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const whStockMap = @json($warehouseStockMap);
            const shopStockMap = @json($shopStockMap);
            const initialWhTotal = {{ $warehouse }};
            const initialShopTotal = {{ $shopStock }};
            
            const whTotals = {
                @foreach ($warehouses as $wh)
                    "{{ $wh->id }}": {{ $warehouseStocks[$wh->id] ?? 0 }},
                @endforeach
            };
            const shopTotals = {
                @foreach ($shops as $sh)
                    "{{ $sh->id }}": {{ $shopStocks[$sh->id] ?? 0 }},
                @endforeach
            };

            function formatWeight(val) {
                val = parseFloat(val) || 0;
                if (val >= 1000) {
                    return (val / 1000).toFixed(2) + ' T';
                }
                return val.toFixed(1) + ' kg';
            }

            function calculateWhStock(locId, prodId) {
                if (locId === 'all' && prodId === 'all') {
                    return initialWhTotal;
                }
                if (locId === 'all') {
                    let total = 0;
                    for (const wId in whStockMap) {
                        total += parseFloat(whStockMap[wId][prodId]) || 0;
                    }
                    return total;
                }
                if (prodId === 'all') {
                    return whTotals[locId] || 0;
                }
                return (whStockMap[locId] && whStockMap[locId][prodId]) ? parseFloat(whStockMap[locId][prodId]) : 0;
            }

            function calculateShopStock(locId, prodId) {
                if (locId === 'all' && prodId === 'all') {
                    return initialShopTotal;
                }
                if (locId === 'all') {
                    let total = 0;
                    for (const sId in shopStockMap) {
                        total += parseFloat(shopStockMap[sId][prodId]) || 0;
                    }
                    return total;
                }
                if (prodId === 'all') {
                    return shopTotals[locId] || 0;
                }
                return (shopStockMap[locId] && shopStockMap[locId][prodId]) ? parseFloat(shopStockMap[locId][prodId]) : 0;
            }

            // Warehouse Stock Selector
            const whSelect = document.getElementById('wh-stock-select');
            const whProductSelect = document.getElementById('wh-product-select');
            const whValue = document.getElementById('wh-stock-value');
            
            function updateWhDisplay() {
                if (whSelect && whProductSelect && whValue) {
                    const locId = whSelect.value;
                    const prodId = whProductSelect.value;
                    const stock = calculateWhStock(locId, prodId);
                    whValue.innerHTML = `<i class="bi bi-box-seam me-1"></i> ${formatWeight(stock)}`;
                }
            }
            
            if (whSelect) whSelect.addEventListener('change', updateWhDisplay);
            if (whProductSelect) whProductSelect.addEventListener('change', updateWhDisplay);

            // Shop Stock Selector
            const shopSelect = document.getElementById('shop-stock-select');
            const shopProductSelect = document.getElementById('shop-product-select');
            const shopValue = document.getElementById('shop-stock-value');
            
            function updateShopDisplay() {
                if (shopSelect && shopProductSelect && shopValue) {
                    const locId = shopSelect.value;
                    const prodId = shopProductSelect.value;
                    const stock = calculateShopStock(locId, prodId);
                    shopValue.innerHTML = `<i class="bi bi-shop me-1"></i> ${formatWeight(stock)}`;
                }
            }
            
            if (shopSelect) shopSelect.addEventListener('change', updateShopDisplay);
            if (shopProductSelect) shopProductSelect.addEventListener('change', updateShopDisplay);

            // Receivables Selector
            const recSelect = document.getElementById('receivables-select');
            const recValue = document.getElementById('receivables-value');
            if (recSelect && recValue) {
                recSelect.addEventListener('change', function () {
                    const amt = parseFloat(this.value) || 0;
                    recValue.innerHTML = `<i class="bi bi-currency-rupee me-1"></i>${amt.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                });
            }

            // Low Stock Selector
            const lowSelect = document.getElementById('lowstock-select');
            const lowValue = document.getElementById('lowstock-value');
            if (lowSelect && lowValue) {
                lowSelect.addEventListener('change', function () {
                    lowValue.innerHTML = `
                        <i class="bi bi-exclamation-triangle-fill me-1" style="animation: blinkWarning 1.4s infinite ease-in-out;"></i>
                        ${this.value} Items
                    `;
                });
            }

            // Product Stock Search
            const searchInput = document.getElementById('product-search');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const query = this.value.toLowerCase().trim();
                    const rows = document.querySelectorAll('.product-row');
                    rows.forEach(row => {
                        const name = row.getAttribute('data-name') || '';
                        const sku = row.getAttribute('data-sku') || '';
                        const location = row.getAttribute('data-location') || '';
                        if (name.includes(query) || sku.includes(query) || location.includes(query)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
@endsection
