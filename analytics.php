<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<div class="container">
    <script src="assets/js/kingsoft/chart.js"></script>
    <div class="row">
        <div class="col-12">
            <div class="card card-nav-tabs" x-init="()=>{
                Alpine.store('reports').jobsPerDay.getJobsPerDay();
            }" x-data="{chart: null, chartCanvas: null}">
                <div class="card-header card-header-info position-relative">
                    <h4 class="cart-title mt-2">Jobs Per Day</h4>
                    <template x-data x-if="!$store.reports.jobsPerDay.isLoaded">
                        <i class="now-ui-icons loader_refresh spin"></i>
                    </template>
                    <div class="filters" style="position: absolute; top: 24px; right: 24px;">
                        <div class="dropdown jobs-per-day" x-init="()=> {
                            $('div.jobs-per-day.dropdown').on('hide.bs.dropdown', (e)=>{
                                if (e.clickEvent) {
                                    e.preventDefault();
                                }
                            })
                        }">
                            <button class="filter-button dropdown-toggle round-icon-button" 
                                style="top: 24px; right: 24px;"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                data-offset="-10,20"
                            >
                                <i class="now-ui-icons design_bullet-list-67"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right p-3">
                                <form x-data action="" @submit.prevent="()=>{
                                    Alpine.store('reports').jobsPerDay.getJobsPerDay().then(()=>{
                                        chart.destroy()
                                        chart = jobsPerDayChart(chartCanvas)
                                    }).catch(e=>console.error(e));
                                }">
                                    <div class="form-group">
                                        <label for="">Unit of time</label>
                                        <select name="time_unit" class="form-control" x-model="$store.reports.jobsPerDay.filters.timeUnit">
                                            <option value="day">Day</option>
                                            <option value="month">Month</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="">Period</label>
                                        <div class="form-check">
                                            <label class="form-check-label">
                                                <input class="form-check-input" type="checkbox" 
                                                    :value="$store.reports.jobsPerDay.filters.period.allTime"
                                                    x-model="$store.reports.jobsPerDay.filters.period.allTime"
                                                >
                                                All time
                                                <span class="form-check-sign">
                                                    <span class="check"></span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="form-group" x-transition x-cloak x-show="!$store.reports.jobsPerDay.filters.period.allTime">
                                        <label for="">From</label>
                                        <input type="date" class="form-control" x-model="$store.reports.jobsPerDay.filters.period.from">
                                    </div>
                                    <div class="form-group" x-transition x-cloak x-show="!$store.reports.jobsPerDay.filters.period.allTime">
                                        <label for="">To</label>
                                        <input type="date" class="form-control" x-model="$store.reports.jobsPerDay.filters.period.to">
                                    </div>
                                    <button type="submit" class="btn btn-info w-100">Apply</button>
                                    <button type="button" class="btn btn-danger w-100" @click="()=>{
                                        $store.reports.jobsPerDay.filters.clearFilters()
                                        Alpine.store('reports').jobsPerDay.getJobsPerDay().then(()=>{
                                            chart.destroy()
                                            chart = jobsPerDayChart(chartCanvas)
                                        }).catch(e=>console.error(e));
                                    }">
                                        Clear
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <template x-if="$store.reports.jobsPerDay.error && !$store.reports.jobsPerDay.data?.report?.length">
                        <div x-data id="reports.jobsPerDay-error-message" class="error-message">
                            <img 
                                :src="$store.reports.jobsPerDay.error.status == 500 ? './assets/img/server_error.svg' : './assets/img/no_data.svg'" 
                                alt="" class="error-illustration"
                            >
                            <span class="error-description" x-text="$store.reports.jobsPerDay.error.message"></span>
                        </div>
                    </template>
                    <div x-data x-transition x-show="$store.reports.jobsPerDay.isLoaded && $store.reports.jobsPerDay.data?.report?.length"
                        x-init="$watch('$store.reports.jobsPerDay.data', (data)=>{
                            chartCanvas = document.getElementById('jobs-per-day')
                            chart = jobsPerDayChart(chartCanvas)
                        })"
                    >
                        <canvas id="jobs-per-day"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6">
            <div class="card card-nav-tabs" x-init="()=>{
                Alpine.store('reports').jobsPerTag.getJobsPerTag();
            }" x-data="{chart: null, chartCanvas: null}">
                <div class="card-header card-header-info position-relative">
                    <h4 class="cart-title mt-2">Jobs Per Tag</h4>
                    <template x-data x-if="!$store.reports.jobsPerTag.isLoaded">
                        <i class="now-ui-icons loader_refresh spin"></i>
                    </template>
                    <div class="filters" x-id="['tags-modal']" @tags-changed="$store.reports.jobsPerTag.filters.tags = $event.detail" 
                        style="position: absolute; top: 24px; right: 24px;">
                        <?php include('tags.php') ?>
                        <div class="dropdown jobs-per-tag" x-init="()=> {
                            $('div.jobs-per-tag.dropdown').on('hide.bs.dropdown', (e)=>{
                                if (e.clickEvent) {
                                    e.preventDefault();
                                }
                            })
                        }">
                            <button class="filter-button dropdown-toggle round-icon-button" 
                                style="top: 24px; right: 24px;"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                data-offset="-10,20"
                            >
                                <i class="now-ui-icons design_bullet-list-67"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right p-3">
                                <form x-data action="" @submit.prevent="()=>{
                                    Alpine.store('reports').jobsPerTag.getJobsPerTag().then(()=>{
                                        chart.destroy()
                                        chart = jobsPerTagChart(chartCanvas)
                                    }).catch(e=>console.error(e));
                                }">
                                    <div class="form-group filter-action w-100">
                                        <button type="button" class="filter-button" @click="()=>{
                                            $(`#${$id('tags-modal')}`).modal('show')
                                        }">
                                            Tags
                                        </button>
                                        <button type="button" @click="$store.reports.jobsPerTag.filters.tags.splice(0, $store.reports.jobsPerTag.filters.tags.length)" 
                                            class="icon-button" 
                                            x-show="$store.reports.jobsPerTag.filters.tags.length"
                                        >
                                            <i x-data class="now-ui-icons ui-1_simple-remove"></i>
                                        </button>
                                    </div>
                                    <div class="form-group">
                                        <label for="">Period</label>
                                        <div class="form-check">
                                            <label class="form-check-label">
                                                <input class="form-check-input" type="checkbox" 
                                                    :value="$store.reports.jobsPerTag.filters.period.allTime"
                                                    x-model="$store.reports.jobsPerTag.filters.period.allTime"
                                                >
                                                All time
                                                <span class="form-check-sign">
                                                    <span class="check"></span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="form-group" x-transition x-cloak x-show="!$store.reports.jobsPerTag.filters.period.allTime">
                                        <label for="">From</label>
                                        <input type="date" class="form-control" x-model="$store.reports.jobsPerTag.filters.period.from">
                                    </div>
                                    <div class="form-group" x-transition x-cloak x-show="!$store.reports.jobsPerTag.filters.period.allTime">
                                        <label for="">To</label>
                                        <input type="date" class="form-control" x-model="$store.reports.jobsPerTag.filters.period.to">
                                    </div>
                                    <button type="submit" class="btn btn-info w-100">Apply</button>
                                    <button type="button" class="btn btn-danger w-100" @click="()=>{
                                        $store.reports.jobsPerTag.filters.clearFilters()
                                        Alpine.store('reports').jobsPerTag.getJobsPerTag().then(()=>{
                                            chart.destroy()
                                            chart = jobsPerTagChart(chartCanvas)
                                        }).catch(e=>console.error(e));
                                    }">
                                        Clear
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <template x-if="$store.reports.jobsPerTag.error && !$store.reports.jobsPerTag.data.length">
                        <div x-data id="reports.jobsPerTag-error-message" class="error-message">
                            <img 
                                :src="$store.reports.jobsPerTag.error.status == 500 ? './assets/img/server_error.svg' : './assets/img/no_data.svg'" 
                                alt="" class="error-illustration"
                            >
                            <span class="error-description" x-text="$store.reports.jobsPerTag.error.message"></span>
                        </div>
                    </template>
                    <div x-data x-transition x-show="$store.reports.jobsPerTag.isLoaded && $store.reports.jobsPerTag.data.length"
                        x-init="$watch('$store.reports.jobsPerTag.data', (data)=>{
                            chartCanvas = document.getElementById('jobs-per-tag')
                            chart = jobsPerTagChart(chartCanvas)
                        })"
                    >
                        <canvas id="jobs-per-tag"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card card-nav-tabs" x-init="()=>{
                Alpine.store('reports').jobsPerClient.getJobsPerClient();
            }" x-data="{chart: null, chartCanvas: null}" x-effect="console.log('chart: ', chart)">
                <div class="card-header card-header-info position-relative">
                    <h4 class="cart-title mt-2">Jobs Per Client</h4>
                    <template x-data x-if="!$store.reports.jobsPerClient.isLoaded">
                        <i class="now-ui-icons loader_refresh spin"></i>
                    </template>
                    <div class="filters" style="position: absolute; top: 24px; right: 24px;">
                        <div class="dropdown jobs-per-client" x-init="()=> {
                            $('div.jobs-per-client.dropdown').on('hide.bs.dropdown', (e)=>{
                                if (e.clickEvent) {
                                    e.preventDefault();
                                }
                            })
                        }">
                            <button class="filter-button dropdown-toggle round-icon-button" 
                                style="top: 24px; right: 24px;"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                data-offset="-10,20"
                            >
                                <i class="now-ui-icons design_bullet-list-67"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right p-3">
                                <form x-data action="" @submit.prevent="()=>{
                                    Alpine.store('reports').jobsPerClient.getJobsPerClient().then(()=>{
                                        chart.destroy()
                                        chart = jobsPerClientChart(chartCanvas)
                                    }).catch(e=> {
                                        console.error(e)
                                    })
                                }">
                                    <div class="form-group custom-dropdown w-100" x-data="{isOpen: false, search: ''}" x-init="()=>{
                                        const toggle = document.getElementById('client-search')
                                        const dropdown = document.getElementById('client-select')
                                        window.FloatingUIDOM.autoUpdate(toggle, dropdown, () => {
                                            window.FloatingUIDOM.computePosition(toggle, dropdown, {
                                                placement: 'bottom-end', 
                                                middleware: [
                                                    window.FloatingUIDOM.offset(10),
                                                    window.FloatingUIDOM.flip(),
                                                    window.FloatingUIDOM.shift()
                                                ]
                                            }).then(({y}) => {
                                                Object.assign(dropdown.style, {
                                                    top: `${y}px`,
                                                    bottom: `${y}px`
                                                });
                                            })
                                        }) 
                                    }">
                                        <label for="client-search">Clients</label>
                                        <div class="filter-action clients w-100" style="gap: .1rem;">
                                            <div style="position: relative;">
                                                <input type="text" id="client-search" placeholder="Search Client"
                                                @click="isOpen = !isOpen" x-model="search">

                                                <i class="now-ui-icons arrows-1_minimal-down" :class="isOpen && 'active'"></i>
                                            </div>
                                            <button type="button" @click="$store.reports.jobsPerClient.filters.clients.splice(0, $store.reports.jobsPerClient.filters.clients.length)" 
                                                class="icon-button" 
                                                x-show="$store.reports.jobsPerClient.filters.clients.length"
                                            >
                                                <i x-data class="now-ui-icons ui-1_simple-remove position-static"></i>
                                            </button>
                                        </div>
                                        <div class="dropdown-content" @click.outside="isOpen = false" id="client-select" x-show="isOpen"
                                            x-transition.scale.origin.top style="max-height: 300px; overflow-y: auto;"
                                        >
                                            <template x-for="client in $store.clients.list.filter(c=>c.name.toLowerCase().includes(search.toLowerCase()))">
                                                <div class="form-check">
                                                    <label class="form-check-label">
                                                        <input class="form-check-input" type="checkbox" 
                                                            :value="client.id"
                                                            x-model="$store.reports.jobsPerClient.filters.clients"
                                                            :checked="$store.reports.jobsPerClient.filters.clients.includes(client.id)"
                                                        >
                                                        <span class="form-check-sign">
                                                            <span class="check"></span>
                                                        </span>
                                                        <span x-text="client.name"></span>
                                                    </label>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="">Period</label>
                                        <div class="form-check">
                                            <label class="form-check-label">
                                                <input class="form-check-input" type="checkbox" 
                                                    :value="$store.reports.jobsPerClient.filters.period.allTime"
                                                    x-model="$store.reports.jobsPerClient.filters.period.allTime"
                                                >
                                                All time
                                                <span class="form-check-sign">
                                                    <span class="check"></span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="form-group" x-transition x-cloak x-show="!$store.reports.jobsPerClient.filters.period.allTime">
                                        <label for="">From</label>
                                        <input type="date" class="form-control" x-model="$store.reports.jobsPerClient.filters.period.from">
                                    </div>
                                    <div class="form-group" x-transition x-cloak x-show="!$store.reports.jobsPerClient.filters.period.allTime">
                                        <label for="">To</label>
                                        <input type="date" class="form-control" x-model="$store.reports.jobsPerClient.filters.period.to">
                                    </div>
                                    <button type="submit" class="btn btn-info w-100">Apply</button>
                                    <button type="button" class="btn btn-danger w-100" @click="()=>{
                                        $store.reports.jobsPerClient.filters.clearFilters()
                                        Alpine.store('reports').jobsPerClient.getJobsPerClient().then(()=>{
                                            chart.destroy()
                                            chart = jobsPerClientChart(chartCanvas)
                                        }).catch(e=>console.error(e))
                                    }">
                                        Clear
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <template x-data x-if="$store.reports.jobsPerClient.error && !$store.reports.jobsPerClient.data.length">
                        <div x-data id="reports.jobsPerClient-error-message" class="error-message">
                            <img 
                                :src="$store.reports.jobsPerClient.error.status == 500 ? './assets/img/server_error.svg' : './assets/img/no_data.svg'" 
                                alt="" class="error-illustration"
                            >
                            <span class="error-description" x-text="$store.reports.jobsPerClient.error.message"></span>
                        </div>
                    </template>
                    <div x-data x-transition x-show="$store.reports.jobsPerClient.isLoaded && $store.reports.jobsPerClient.data.length"
                         x-init="$watch('$store.reports.jobsPerClient.data', (data) => {
                            if(data?.length) {
                                chartCanvas = document.getElementById('jobs-per-client')
                                chart = jobsPerClientChart(chartCanvas)
                            }
                         })">
                        <canvas id="jobs-per-client"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>