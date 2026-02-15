// import { CountUp } from 'countup.js'
import ApexCharts from 'apexcharts';

//  {"ymax":18,"xmax":34,"datasets":[{"info":{"teamid":4502,"clubid":1,"name":"Chemnitzer FC","short_name":"CFC","logo":"\u003Cimg src=\u0022\/fileadmin\/_processed_\/0\/c\/csm_Chemnitzer_FC_76cdc37f79.png\u0022 width=\u002271\u0022 height=\u002290\u0022   alt=\u0022\u0022 \u003E"},"data":[[1,6],[2,1],[3,1],[4,1],[5,1],[6,1],[7,1],[8,1],[9,1],[10,1],[11,1],[12,1],[13,1],[14,1],[15,1],[16,1],[17,1],[18,1],[19,1],[20,1],[21,1],[22,1],[23,1],[24,1],[25,1],[26,1],[27,1],[28,1],[29,1],[30,1],[31,1],[32,1],[33,1],[34,1]]}]};

function createLeagueChart(chartData: LeagueTableData, containerId: string): void {
    const series = chartData.datasets.map(dataset => ({
        name: dataset.info.short_name || dataset.info.name,
        data: dataset.data.map(point => point[1])
    }));

    const options: ApexCharts.ApexOptions = {
        chart: {
            type: 'line',
            height: 400,
            sparkline: {
                enabled: false
            },
            zoom: {
                enabled: false
            }
        },
        series: series,
        xaxis: {
            categories: Array.from({ length: chartData.xmax }, (_, i) => `${i + 1}. Spieltag`),
            max: chartData.xmax
        },
        yaxis: {
            title: {
                text: 'Platz'
            },
            max: chartData.ymax,
            min: 1,
            reversed: true
        },
        title: {
            text: 'Tabellenfahrt',
            align: 'left'
        },
        stroke: {
            curve: 'smooth',
            width: 5
        },
        legend: {
            position: 'bottom'
        },
        tooltip: {
            custom: function({ series, seriesIndex, dataPointIndex, w }: any) {
                const dataset = chartData.datasets[seriesIndex];
                const logo = dataset.info.logo;
                const teamName = dataset.info.name;
                const points = series[seriesIndex][dataPointIndex];
                const spieltag = dataPointIndex + 1;

                return `
                    <div style="padding: 12px; background: #fff; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                        <div style="margin-bottom: 8px;">${logo}</div>
                        <div style="font-weight: bold; margin-bottom: 4px;">${teamName}</div>
                        <div><strong>${spieltag}. Spieltag:</strong> Platz ${points}</div>
                    </div>
                `;
            }
        }
    };

    const chart = new ApexCharts(document.querySelector(`#${containerId}`), options);
    chart.render();
}

function init() {
    const elements = document.querySelectorAll<HTMLElement>('.tablerun');

    elements.forEach(entry => {
        const chartData: LeagueTableData = JSON.parse(decodeURIComponent(atob(entry.dataset.chartdata || '')));
        
        const containerId = `chart-${Math.random().toString(36).substr(2, 9)}`;
        const chartContainer = document.createElement('div');
        chartContainer.id = containerId;
        entry.appendChild(chartContainer);

        createLeagueChart(chartData, containerId);
    });
}

document.addEventListener('DOMContentLoaded', init);


interface TeamInfo {
  teamid: number;
  clubid: number;
  name: string;
  short_name: string;
  logo: string;
}

interface DataPoint {
  0: number;  // Spiel-Nr
  1: number;  // Punkte
}

interface Dataset {
  info: TeamInfo;
  data: DataPoint[];
}

interface LeagueTableData {
  ymax: number;
  xmax: number;
  datasets: Dataset[];
}
