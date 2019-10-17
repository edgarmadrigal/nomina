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
                    ['Other', 30],
                    ['Desktop', 10],
                    ['Tablet', 40],
                    ['Mobile', 50],
                ],
                type: 'donut',
                onclick: function (d, i) { console.log("onclick", d, i); },
                onmouseover: function (d, i) { console.log("onmouseover", d, i); },
                onmouseout: function (d, i) { console.log("onmouseout", d, i); }
            },
            donut: {
                label: {
                    show: false
                },
                title: "Visits",
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
            data: [{
                period: '2017-01-01',
                Enero: 10,
                Febrero: 80,
                Marzo: 90
            }, {
                period: '2017-01-02',
                Enero: 10,
                Febrero: 80,
                Marzo: 90         
            }, {
                period: '2017-01-03',
                Enero: 90,
                Febrero: 76,
                Marzo: 90
            }],

            xkey: 'period',
            xLabels: 'day',
            ykeys: ['Enero','Febrero','Marzo'],
            labels: ['Enero','Febrero','Marzo'],
            pointSize: 2,
            fillOpacity: 0,
            pointStrokeColors: ['#20aee3', '#24d2b5', '#6772e5'],
            behaveLikeLine: true,
            gridLineColor: 'gray',
            lineWidth: 3,
            hideHover: 'auto',
            resize: true,
            //colors:['red', '#24d2b5', '#6772e5'],

        });
    
   var data = [
        { y: '2014', a: 50, b: 90},
        { y: '2015', a: 65,  b: 75},
        { y: '2016', a: 50,  b: 50},
        { y: '2017', a: 75,  b: 60},
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
        {label: "Friends", value: 30},
        {label: "Allies", value: 15},
        {label: "Enemies", value: 45},
        {label: "Neutral", value: 10}
    ]
    });



});