function splitDataSetBy(data, key) {
    return data.reduce((acc, record) => {
        const dataset = acc.find(d=>d.label === record[key])
        if(!dataset) {
            acc.push({
                label: record[key],
                data: [{x: `${record.time_unit}`, y: record.jobs}]
            })
            return acc
        }
        dataset.data.push({x: `${record.time_unit}`, y: record.jobs})
        return acc
    }, [])
}
function jobsPerDayChart(element) {
    const data = Alpine.store('reports').jobsPerDay.data
    return new Chart(element, {
        type: 'bar',
        options: {
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
        },
        data: {
            datasets: [
                ...splitDataSetBy(data?.report, 'status'),
                data?.response_times && {
                    type: 'line',
                    label: 'Average response time (Hrs)',
                    hidden: true,
                    data: data?.response_times.map(t=>{
                        return {x: `${t.time_unit}`, y: t.avg_response_time}
                    })
                }
            ]
        }
    })
}

function jobsPerTagChart(element) {
    const data = Alpine.store('reports').jobsPerTag.data
    
    return new Chart(element, {
        type: 'pie',
        options: {
            aspectRatio: 1,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            resTime = Alpine.store('reports').jobsPerTag.data[context.dataIndex]?.avg_response_time
                            return [` ${context.label}: ${context.parsed}`, `Average response time: ${parseFloat(resTime)?.toFixed(2)} Hrs`]
                        }
                    }
                }
            }
        },
        data: {
            labels: data?.map(r=>r.tag),
            datasets: [
                {
                    label: 'jobs',
                    data: data?.map(r=>r.jobs),
                    backgroundColor: data?.map(r=>r.colorcode)
                    
                },
            ]
        },
    })
}

function jobsPerClientChart(element) {
    const data = Alpine.store('reports').jobsPerClient.data
    return new Chart(element, {
        type: 'pie',
        options: {
            aspectRatio: 1,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            resTime = Alpine.store('reports').jobsPerClient.data[context.dataIndex]?.avg_response_time
                            return [` ${context.label}: ${context.parsed}`, `Average response time: ${parseFloat(resTime)?.toFixed(2)} Hrs`]
                        }
                    }
                }
            }
        },
        data: {
            labels: data?.map(r=>r.client),
            datasets: [
                {
                    label: 'jobs',
                    data: data?.map(r=>r.jobs),
                }
            ]
        }
    })
}