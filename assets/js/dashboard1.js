/*
Template Name: Admin Pro Admin
Author: Wrappixel
Email: niravjoshi87@gmail.com
File: js
s*/
$(function () {
      //"use strict";
            // Our Visitor
        var chart = c3.generate({
            bindto: '#visitor',
            data: {
                columns: [
                    ['TBG', ~~(Math.random() * (25 - 5) + 5)],
                    ['MMS', ~~(Math.random() * (25 - 10) + 10)],
                    ['AE', ~~(Math.random() * (25 - 5) + 5)],
                    ['TT1', ~~(Math.random() * (25 - 15) + 15)],
                ],
                type: 'donut',
                //onclick: function (d, i) { console.log("onclick", d, i); },
                //onmouseover: function (d, i) { console.log("onmouseover", d, i); },
                //onmouseout: function (d, i) { console.log("onmouseout", d, i); }
            },
            donut: {
                label: {
                    show: false
                },
                title: "Faltas",
                width: 20,
            },
            legend: {
                hide: true
                //or hide: 'data1'
                //or hide: ['data1', 'data2']
            },
            color: {
                pattern: ['#eceff1', '#24d2b5', '#6772e5', '#20aee3']
            }
        });

        // Sales chart
        Morris.Area({
            element: 'sales-chart',
            data: [
                {
                    period: '2022-01-01',
                    Retardos: ~~(Math.random() * (10 - 10) + 10),
                    Faltas: ~~(Math.random() * (100 - 10) + 10),
                    Asistencias: ~~(Math.random() * (100 - 10) + 10)         
                },
                {
                    period: '2022-01-02',
                    Retardos: ~~(Math.random() * (30 - 30) + 20),
                    Faltas: ~~(Math.random() * (80 - 70) + 70),
                    Asistencias: ~~(Math.random() * (80 - 70) + 70)
                },
                {
                    period: '2022-01-03',
                    Retardos: ~~(Math.random() * (35 - 40) + 40),
                    Faltas: ~~(Math.random() * (75 - 55) + 55),
                    Asistencias: ~~(Math.random() * (70 - 50) + 50)
                }
             ,{
                period: '2022-01-04',
                Retardos: ~~(Math.random() * (60 - 10) + 40),
                Faltas: ~~(Math.random() * (50 - 40) + 40),
                Asistencias: ~~(Math.random() * (50 - 40) + 40)    
            } , {
                period: '2022-01-05',
                Retardos: ~~(Math.random() * (70 - 10) +55),
                Faltas: ~~(Math.random() * (40 - 25) + 25),
                Asistencias: ~~(Math.random() * (40 - 25) + 25)
            } ,  
            {
                period: '2022-01-06',
                    Retardos: ~~(Math.random() * (100 - 80) + 90),
                    Faltas: ~~(Math.random() * (20 - 10) + 10),
                    Asistencias: ~~(Math.random() *(20 - 10) + 10),
            }, 
        ],
            xkey: 'period',
            xLabels: 'day',
            ykeys: ['Retardos','Faltas','Asistencias'],
            labels: ['Retardos','Faltas','Asistencias'],
            pointSize: 2,
            fillOpacity: 0,
            pointStrokeColors: ['#20aee3', '#24d2b5', '#6772e5'],
            behaveLikeLine: true,
            gridLineColor: 'gray',
            lineWidth:3,
            hideHover: 'auto',
            resize: true,
            colors:['red', '#ff9041', '#ff9041'],

        });
    
   var data = [
        { y: '2014', a: 50, b: 90},
        { y: '2015', a: 65,  b: 75},
        { y: '2016', a: 50,  b: 50},
        { y: '2022', a: 75,  b: 60},
        { y: '2018', a: 80,  b: 65},
        { y: '2019', a: 90,  b: 70},
        { y: '2020', a: 100, b: 75},
        { y: '2021', a: 115, b: 75},
        { y: '2022', a: 120, b: 85},
        { y: '2023', a: 145, b: 85},
        { y: '2024', a: 160, b: 95}
    ],
    
    config = {
        data: data,
        xkey: 'y',
        ykeys: ['a', 'b'],
        labels: ['Total Income', 'Total Outcome'],
        fillOpacity: 0.6,
        hideHover: 'auto',
        behaveLikeLine: true,
        resize: true,
        pointFillColors:['#ffffff'],
        pointStrokeColors: ['black'],
        lineColors:['gray','red']
    };
    config.element = 'area-chart';
    Morris.Area(config);
    config.element = 'sales-chart';
    Morris.Line(config);
    config.element = 'bar-chart';
    Morris.Bar(config);
    config.element = 'stacked';
    config.stacked = true;
    Morris.Bar(config);
    Morris.Donut({
        element: 'pie-chart',
    data: [
        {label: "TBG", value: 30},
        {label: "MMS", value: 15},
        {label: "TT1", value: 45},
        {label: "AE", value: 10}
    ]
    });



});