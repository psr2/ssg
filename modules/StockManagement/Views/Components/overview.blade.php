@extends('dashboard::dashboard')

@section('stock_overview')

  <style>
    :root{
      --safe:#198754;   /* green */
      --warn:#fd7e14;   /* orange */
      --crit:#dc3545;   /* red */
      --ink:#0f172a;    /* slate-900-ish */
    }
    body{ font-family:'Poppins',sans-serif; background:#f7f8fb; color:#2b2f3a; }
    .card{ border:none; border-radius:16px; box-shadow:0 10px 25px rgba(2,15,29,.06); }
    .card-header{ border-top-left-radius:16px; border-top-right-radius:16px; }

    /* Accordion Header */
    .accordion-button{
      gap:16px;
      padding:1rem 1.25rem;
      border-radius:12px!important;
      background:#fff;
      box-shadow:inset 0 0 0 1px rgba(15,23,42,.04);
    }
    .accordion-button:not(.collapsed){
      background:#f9fbff;
      box-shadow:inset 0 0 0 2px rgba(13,110,253,.15);
    }
    .acc-title{ font-weight:600; color:var(--ink); line-height:1.1; }
    .acc-sub{ font-size:.85rem; color:#6b7280; }
    .stock-chip{
      font-size:.8rem; font-weight:600; padding:.25rem .5rem; border-radius:999px;
      background:#eef2ff; color:#3b82f6; white-space:nowrap;
    }
    .inline-meter{ width:160px; height:8px; background:#e9eef6; border-radius:999px; overflow:hidden; }
    .inline-meter > span{ display:block; height:100%; border-radius:999px; }

    /* Progress in body */
    .progress{ height:10px; background:#eef2f7; border-radius:12px; }
    .progress-bar{ border-radius:12px; }
    .fact{ font-size:.9rem; }
    .fact .label{ color:#6b7280; }
    .fact .value{ font-weight:600; }

    /* Alerts */
    .alert-card{
      border:1px solid rgba(220,53,69,.15);
      background:#fff7f7; border-radius:12px; padding:12px 14px; margin-bottom:10px;
      cursor:pointer; transition:transform .08s ease;
    }
    .alert-card:hover{ transform:translateY(-2px); }
    .alert-icon{ width:10px; height:10px; border-radius:999px; display:inline-block; margin-right:8px; }
    .alert-critical .alert-icon{ background:var(--crit); }
    .alert-warning{ border-color:rgba(253,126,20,.2); background:#fff9f3; }
    .alert-warning .alert-icon{ background:var(--warn); }

    /* Sparkline wrapper */
    .sparkline{
      width:100%; max-width:220px;
      border-radius:8px; padding:6px 8px; background:#fbfcff; border:1px solid #edf2fd;
    }

    /* Table */
    .table thead{ background:#f1f3f7; }
    .status-badge{ font-size:.75rem; }

    /* Scrollable accordion area (keeps layout tidy) */
    .accordion-scroll {
      max-height: 480px;      /* adjust to taste */
      overflow-y: auto;
      padding-right: 6px;
    }

    /* Show more button */
    .show-more-wrap { text-align:center; margin-top:10px; }
    .btn-show-more { font-weight:600; border-radius:999px; }
  </style>

<div class="container py-3">
  <div class="row ">

    <!-- LEFT: 70% Depleting Items -->
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header bg-primary text-white fw-semibold">Depleting Items</div>

        <!-- scrollable container for accordion -->
        <div class="card-body accordion-scroll" id="accordionScroll">
          <div class="accordion" id="depletingAccordion">

            <!-- NOTE: First 5 are "top" (visible by default), remaining items get class 'extra-item' -->
            <!-- ITEM 1 -->
            <div class="accordion-item border-0" data-index="1">
              <h2 class="accordion-header" id="h-on-123">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#c-on-123" aria-expanded="false" aria-controls="c-on-123">
                  <div class="flex-grow-1">
                    <div class="acc-title">Onion — Batch #ON123</div>
                    <div class="acc-sub">Vendor: Fresh Farms • Location: Shop A</div>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <span class="stock-chip">120 / 500 kg</span>
                    <small class="text-muted">24%</small>
                    <div class="inline-meter" role="meter" aria-valuenow="24"><span style="width:24%; background:var(--warn)"></span></div>
                  </div>
                </button>
              </h2>
              <div id="c-on-123" class="accordion-collapse collapse" data-bs-parent="#depletingAccordion">
                <div class="accordion-body">
                  <div class="row g-3 align-items-center">
                    <div class="col-md-7">
                      <div class="row g-2">
                        <div class="col-6 fact"><span class="label">Remaining:</span> <span class="value">120 kg</span></div>
                        <div class="col-6 fact"><span class="label">Total:</span> <span class="value">500 kg</span></div>
                        <div class="col-6 fact"><span class="label">Threshold:</span> <span class="value text-warning">100 kg</span></div>
                        <div class="col-6 fact"><span class="label">Vendor:</span> <span class="value">Fresh Farms</span></div>
                      </div>
                      <div class="mt-3">
                        <div class="progress"><div class="progress-bar bg-warning" style="width:24%"></div></div>
                      </div>
                    </div>
                    <div class="col-md-5">
                      <div class="sparkline">
                        <svg viewBox="0 0 100 30" width="100%" height="30" preserveAspectRatio="none">
                          <polyline fill="none" stroke="#fd7e14" stroke-width="2"
                            points="0,5 16,8 32,11 48,14 64,17 82,21 100,24"/>
                        </svg>
                        <small class="text-muted">Last 7 days</small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>


            <!-- ITEM 3 -->
            <div class="accordion-item border-0" data-index="3">
              <h2 class="accordion-header" id="h-pt-001">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#c-pt-001" aria-expanded="false" aria-controls="c-pt-001">
                  <div class="flex-grow-1">
                    <div class="acc-title">Potato — Batch #PT001</div>
                    <div class="acc-sub">Vendor: RootSource • Location: Shop B</div>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <span class="stock-chip">30 / 100 kg</span>
                    <small class="text-muted">30%</small>
                    <div class="inline-meter"><span style="width:30%; background:var(--warn)"></span></div>
                  </div>
                </button>
              </h2>
              <div id="c-pt-001" class="accordion-collapse collapse" data-bs-parent="#depletingAccordion">
                <div class="accordion-body">
                  <div class="row g-3 align-items-center">
                    <div class="col-md-7">
                      <div class="row g-2">
                        <div class="col-6 fact"><span class="label">Remaining:</span> <span class="value">30 kg</span></div>
                        <div class="col-6 fact"><span class="label">Total:</span> <span class="value">100 kg</span></div>
                        <div class="col-6 fact"><span class="label">Threshold:</span> <span class="value text-warning">40 kg</span></div>
                        <div class="col-6 fact"><span class="label">Vendor:</span> <span class="value">RootSource</span></div>
                      </div>
                      <div class="mt-3">
                        <div class="progress"><div class="progress-bar bg-warning" style="width:30%"></div></div>
                      </div>
                    </div>
                    <div class="col-md-5">
                      <div class="sparkline">
                        <svg viewBox="0 0 100 30" width="100%" height="30" preserveAspectRatio="none">
                          <polyline fill="none" stroke="#fd7e14" stroke-width="2"
                            points="0,8 16,10 32,12 48,16 64,18 82,20 100,22"/>
                        </svg>
                        <small class="text-muted">Last 7 days</small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ITEM 4 -->
            <div class="accordion-item border-0" data-index="4">
              <h2 class="accordion-header" id="h-tm-001">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#c-tm-001" aria-expanded="false" aria-controls="c-tm-001">
                  <div class="flex-grow-1">
                    <div class="acc-title">Tomato — Batch #TM001</div>
                    <div class="acc-sub">Vendor: GreenGrow • Location: Shop C</div>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <span class="stock-chip">0 / 100 kg</span>
                    <small class="text-muted">0%</small>
                    <div class="inline-meter"><span style="width:0%; background:var(--crit)"></span></div>
                  </div>
                </button>
              </h2>
              <div id="c-tm-001" class="accordion-collapse collapse" data-bs-parent="#depletingAccordion">
                <div class="accordion-body">
                  <div class="row g-3 align-items-center">
                    <div class="col-md-7">
                      <div class="row g-2">
                        <div class="col-6 fact"><span class="label">Remaining:</span> <span class="value">0 kg</span></div>
                        <div class="col-6 fact"><span class="label">Total:</span> <span class="value">100 kg</span></div>
                        <div class="col-6 fact"><span class="label">Threshold:</span> <span class="value text-danger">60 kg</span></div>
                        <div class="col-6 fact"><span class="label">Vendor:</span> <span class="value">GreenGrow</span></div>
                      </div>
                      <div class="mt-3">
                        <div class="progress"><div class="progress-bar bg-danger" style="width:0%"></div></div>
                      </div>
                    </div>
                    <div class="col-md-5">
                      <div class="sparkline">
                        <svg viewBox="0 0 100 30" width="100%" height="30" preserveAspectRatio="none">
                          <polyline fill="none" stroke="#dc3545" stroke-width="2"
                            points="0,4 16,7 32,12 48,18 64,24 82,28 100,28"/>
                        </svg>
                        <small class="text-muted">Last 7 days</small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ITEM 5 -->
            <div class="accordion-item border-0" data-index="5">
              <h2 class="accordion-header" id="h-og-001">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#c-og-001" aria-expanded="false" aria-controls="c-og-001">
                  <div class="flex-grow-1">
                    <div class="acc-title">Orange — Batch #OG001</div>
                    <div class="acc-sub">Vendor: Citrus Co • Location: Warehouse 2</div>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <span class="stock-chip">25 / 200 kg</span>
                    <small class="text-muted">12%</small>
                    <div class="inline-meter"><span style="width:12%; background:var(--warn)"></span></div>
                  </div>
                </button>
              </h2>
              <div id="c-og-001" class="accordion-collapse collapse" data-bs-parent="#depletingAccordion">
                <div class="accordion-body">
                  <div class="row g-3 align-items-center">
                    <div class="col-md-7">
                      <div class="row g-2">
                        <div class="col-6 fact"><span class="label">Remaining:</span> <span class="value">25 kg</span></div>
                        <div class="col-6 fact"><span class="label">Total:</span> <span class="value">200 kg</span></div>
                        <div class="col-6 fact"><span class="label">Threshold:</span> <span class="value text-warning">50 kg</span></div>
                        <div class="col-6 fact"><span class="label">Vendor:</span> <span class="value">Citrus Co</span></div>
                      </div>
                      <div class="mt-3">
                        <div class="progress"><div class="progress-bar bg-warning" style="width:12%"></div></div>
                      </div>
                    </div>
                    <div class="col-md-5">
                      <div class="sparkline">
                        <svg viewBox="0 0 100 30" width="100%" height="30" preserveAspectRatio="none">
                          <polyline fill="none" stroke="#fd7e14" stroke-width="2"
                            points="0,8 16,12 32,15 48,18 64,20 82,24 100,25"/>
                        </svg>
                        <small class="text-muted">Last 7 days</small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- EXTRA ITEMS (hidden by default) -->
            <div class="accordion-item border-0 extra-item d-none" data-index="6">
              <h2 class="accordion-header" id="h-on-125">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c-on-125">
                  <div class="flex-grow-1">
                    <div class="acc-title">Onion — Batch #ON125</div>
                    <div class="acc-sub">Vendor: Fresh Farms • Location: Shop A</div>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <span class="stock-chip">210 / 600 kg</span>
                    <small class="text-muted">35%</small>
                    <div class="inline-meter"><span style="width:35%; background:var(--safe)"></span></div>
                  </div>
                </button>
              </h2>
              <div id="c-on-125" class="accordion-collapse collapse">
                <div class="accordion-body"> <!-- details omitted for brevity --> </div>
              </div>
            </div>

            <div class="accordion-item border-0 extra-item d-none" data-index="7">
              <h2 class="accordion-header" id="h-pt-002">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c-pt-002">
                  <div class="flex-grow-1">
                    <div class="acc-title">Potato — Batch #PT002</div>
                    <div class="acc-sub">Vendor: RootSource • Location: Warehouse 1</div>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <span class="stock-chip">250 / 600 kg</span>
                    <small class="text-muted">42%</small>
                    <div class="inline-meter"><span style="width:42%; background:var(--safe)"></span></div>
                  </div>
                </button>
              </h2>
              <div id="c-pt-002" class="accordion-collapse collapse">
                <div class="accordion-body"> <!-- details omitted --> </div>
              </div>
            </div>

            <div class="accordion-item border-0 extra-item d-none" data-index="8">
              <h2 class="accordion-header" id="h-tm-002">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c-tm-002">
                  <div class="flex-grow-1">
                    <div class="acc-title">Tomato — Batch #TM002</div>
                    <div class="acc-sub">Vendor: GreenGrow • Location: Shop B</div>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <span class="stock-chip">60 / 200 kg</span>
                    <small class="text-muted">30%</small>
                    <div class="inline-meter"><span style="width:30%; background:var(--warn)"></span></div>
                  </div>
                </button>
              </h2>
              <div id="c-tm-002" class="accordion-collapse collapse">
                <div class="accordion-body"> <!-- details omitted --> </div>
              </div>
            </div>

            <div class="accordion-item border-0 extra-item d-none" data-index="9">
              <h2 class="accordion-header" id="h-og-002">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c-og-002">
                  <div class="flex-grow-1">
                    <div class="acc-title">Orange — Batch #OG002</div>
                    <div class="acc-sub">Vendor: Citrus Co • Location: Shop C</div>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <span class="stock-chip">180 / 800 kg</span>
                    <small class="text-muted">22%</small>
                    <div class="inline-meter"><span style="width:22%; background:var(--warn)"></span></div>
                  </div>
                </button>
              </h2>
              <div id="c-og-002" class="accordion-collapse collapse">
                <div class="accordion-body"> <!-- details omitted --> </div>
              </div>
            </div>

            <div class="accordion-item border-0 extra-item d-none" data-index="10">
              <h2 class="accordion-header" id="h-le-001">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c-le-001">
                  <div class="flex-grow-1">
                    <div class="acc-title">Lemon — Batch #LE001</div>
                    <div class="acc-sub">Vendor: Citrus Co • Location: Warehouse 3</div>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <span class="stock-chip">10 / 50 kg</span>
                    <small class="text-muted">20%</small>
                    <div class="inline-meter"><span style="width:20%; background:var(--crit)"></span></div>
                  </div>
                </button>
              </h2>
              <div id="c-le-001" class="accordion-collapse collapse">
                <div class="accordion-body"> <!-- details omitted --> </div>
              </div>
            </div>

            <div class="accordion-item border-0 extra-item d-none" data-index="11">
              <h2 class="accordion-header" id="h-bn-001">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c-bn-001">
                  <div class="flex-grow-1">
                    <div class="acc-title">Banana — Batch #BN001</div>
                    <div class="acc-sub">Vendor: Tropic • Location: Shop D</div>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <span class="stock-chip">5 / 50 kg</span>
                    <small class="text-muted">10%</small>
                    <div class="inline-meter"><span style="width:10%; background:var(--crit)"></span></div>
                  </div>
                </button>
              </h2>
              <div id="c-bn-001" class="accordion-collapse collapse">
                <div class="accordion-body"> <!-- details omitted --> </div>
              </div>
            </div>

            <div class="accordion-item border-0 extra-item d-none" data-index="12">
              <h2 class="accordion-header" id="h-ml-001">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c-ml-001">
                  <div class="flex-grow-1">
                    <div class="acc-title">Mango — Batch #ML001</div>
                    <div class="acc-sub">Vendor: Tropic • Location: Warehouse 2</div>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <span class="stock-chip">60 / 400 kg</span>
                    <small class="text-muted">15%</small>
                    <div class="inline-meter"><span style="width:15%; background:var(--warn)"></span></div>
                  </div>
                </button>
              </h2>
              <div id="c-ml-001" class="accordion-collapse collapse">
                <div class="accordion-body"> <!-- details omitted --> </div>
              </div>
            </div>

          </div>

          <!-- Show more / less -->
          <div class="show-more-wrap">
            <button id="showMoreBtn" class="btn btn-outline-primary btn-sm btn-show-more">Show all</button>
          </div>

        </div>
      </div>
    </div>

    <!-- RIGHT: 30% Alerts -->
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header bg-danger text-white fw-semibold">Stock Alerts</div>
        <div class="card-body">
          <div class="alert-card alert-critical" onclick="openBatch('#c-on-124')">
            <span class="alert-icon"></span>
            <strong>Critical:</strong> Onion Batch #ON124 is below threshold.
          </div>
          <div class="alert-card alert-warning" onclick="openBatch('#c-tm-001')">
            <span class="alert-icon"></span>
            <strong>Warning:</strong> Tomato Batch #TM001 is out of stock.
          </div>
          <div class="alert-card alert-warning" onclick="openBatch('#c-le-001')">
            <span class="alert-icon"></span>
            <strong>Warning:</strong> Lemon Batch #LE001 is low.
          </div>
        </div>
      </div>
    </div>

    <!-- FULL-WIDTH TABLE -->
    <div class="col-12">
      <div class="card mt-4">
        <div class="card-header bg-secondary text-white fw-semibold">All Depleting Items</div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Batch</th>
                  <th>Vendor</th>
                  <th>Location</th>
                  <th>Remaining</th>
                  <th>Threshold</th>
                  <th>Status</th>
                  <th>Updated</th>
                </tr>
              </thead>
              <tbody>
                <!-- replicate rows for demo -->
                <tr><td>Onion</td><td>#ON123</td><td>Fresh Farms</td><td>Shop A</td><td>120 / 500 kg</td><td>100 kg</td><td><span class="badge text-bg-warning">Low</span></td><td>2025-08-27</td></tr>
                <tr><td>Onion</td><td>#ON124</td><td>Agri Supply</td><td>Warehouse 1</td><td>90 / 400 kg</td><td>100 kg</td><td><span class="badge text-bg-danger">Critical</span></td><td>2025-08-27</td></tr>
                <tr><td>Potato</td><td>#PT001</td><td>RootSource</td><td>Shop B</td><td>30 / 100 kg</td><td>40 kg</td><td><span class="badge text-bg-warning">Low</span></td><td>2025-08-27</td></tr>
                <tr><td>Tomato</td><td>#TM001</td><td>GreenGrow</td><td>Shop C</td><td>0 / 100 kg</td><td>60 kg</td><td><span class="badge text-bg-danger">Critical</span></td><td>2025-08-27</td></tr>
                <!-- more demo rows -->
              </tbody>
            </table>
          </div>
          <nav aria-label="Pagination">
            <ul class="pagination justify-content-end mb-0">
              <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
              <li class="page-item active"><a class="page-link" href="#">1</a></li>
              <li class="page-item"><a class="page-link" href="#">2</a></li>
              <li class="page-item"><a class="page-link" href="#">3</a></li>
              <li class="page-item"><a class="page-link" href="#">Next</a></li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
  // toggle show more / less and manage scroll area
  document.addEventListener('DOMContentLoaded', function(){
    const TOP_N = 5;
    const accordion = document.getElementById('depletingAccordion');
    const items = accordion.querySelectorAll('.accordion-item');
    const extras = accordion.querySelectorAll('.extra-item');
    const showBtn = document.getElementById('showMoreBtn');
    const accordionScroll = document.getElementById('accordionScroll');
    let expanded = false;

    // if no extra items, hide button
    if(!extras.length){
      showBtn.classList.add('d-none');
    } else {
      showBtn.textContent = `Show all (${extras.length} more)`;
      showBtn.classList.remove('d-none');
    }

    showBtn.addEventListener('click', function(){
      expanded = !expanded;
      if(expanded){
        // reveal extras
        extras.forEach(e => e.classList.remove('d-none'));
        showBtn.textContent = 'Show less';
        // remove max-height so user can see all (optional)
        accordionScroll.style.maxHeight = 'none';
      } else {
        // hide extras
        extras.forEach(e => e.classList.add('d-none'));
        showBtn.textContent = `Show all (${extras.length} more)`;
        accordionScroll.style.maxHeight = '480px';
        // scroll top of accordion into view
        accordionScroll.scrollTo({ top: 0, behavior: 'smooth' });
      }
    });

    // ensure initial state - only top N visible (we hid extras in markup with d-none)
    // but also enforce if someone forgets to add d-none
    items.forEach((it, idx) => {
      if(idx >= TOP_N && !it.classList.contains('extra-item')){
        it.classList.add('extra-item', 'd-none');
      }
    });
  });

  // open specific batch from alerts
  function openBatch(id){
    const el = document.querySelector(id);
    if(!el) return;
    const collapse = bootstrap.Collapse.getOrCreateInstance(el, {toggle:false});
    collapse.show();
    // if the item is hidden as extra, reveal extras first
    const parentItem = el.closest('.accordion-item');
    if(parentItem && parentItem.classList.contains('d-none')){
      // click show more button programmatically
      const btn = document.getElementById('showMoreBtn');
      if(btn && btn.textContent.startsWith('Show all')){
        btn.click();
      }
    }
    // scroll to the item
    setTimeout(()=> el.scrollIntoView({behavior:'smooth', block:'center'}), 180);
  }
</script>

<!-- Bootstrap JS (bundle includes collapse) -->

@endsection
