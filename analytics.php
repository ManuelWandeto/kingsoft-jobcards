<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<div class="container">
    <script>
        function splitDataSetByStatus(data) {
            return data.reduce((acc, record) => {
                const dataset = acc.find(d=>d.label === record.status)
                if(!dataset) {
                    acc.push({
                        label: record.status,
                        data: [{x: `${record.time_unit}`, y: record.jobs}]
                    })
                    return acc
                }
                dataset.data.push({x: `${record.time_unit}`, y: record.jobs})
                return acc
            }, [])
        }
        function createChart(element, type, datasets, options= {
            aspectRatio: 2,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Job count'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: `${Alpine.store('reports').jobsPerDay.filters.timeUnit}s`
                    }
                }
            }
        }) {
            return new Chart(element, {
                type,
                data: {
                    datasets
                },
                options
            })
        }
    </script>
    <div class="row">
        <div class="col">
            <div class="card card-nav-tabs" x-init="()=>{
                Alpine.store('reports').jobsPerDay.getJobsPerDay();
            }" x-data="{chart: null, chartCanvas: null}" 
                x-init="$watch('$store.reports.jobsPerDay.filters', (val)=>console.log('filters changed'))">
                <div class="card-header card-header-info">
                    <h4 class="cart-title mt-2">Jobs Per Day</h4>
                    <h6 class="card-subtitle">Last 28 days</h6>
                    <div class="dropdown jobs-per-day" x-init="()=> {
                        $('div.jobs-per-day.dropdown').on('hide.bs.dropdown', (e)=>{
                            if (e.clickEvent) {
                                e.preventDefault();
                            }
                        })
                    }">
                        <button class="filter-button dropdown-toggle" 
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                        >
                            <i class="now-ui-icons design_bullet-list-67"></i>
                        </button>
                        <div class="dropdown-menu p-3">
                            <form x-data action="" @submit.prevent="()=>{
                                Alpine.store('reports').jobsPerDay.getJobsPerDay().then(()=>{
                                    const newData = splitDataSetByStatus($store.reports.jobsPerDay.data)
                                    console.log(newData)
                                    chart.destroy()
                                    chart = createChart(chartCanvas, 'bar', newData)

                                });
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
                                <button type="submit">Apply</button>
                                <button type="button">Clear</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <template x-if="$store.reports.jobsPerDay.isLoaded && $store.reports.jobsPerDay.data.length">
                        <div x-data="$store.reports.jobsPerDay" x-init="()=>{
                            chartCanvas = document.getElementById('jobs-per-day')
                            chart = createChart(chartCanvas, 'bar', splitDataSetByStatus(data))
                        }">
                            <canvas id="jobs-per-day"></canvas>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>