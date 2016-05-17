var chartfactory = {};

chartfactory.createBarChart = function(dataset, domelementid, title){

  /*
  NEEDS JQUERY
  */

  /*
  dataset should look like this:
    var dataset = [
      { label: 'label1', value: 10 }, 
      { label: 'label2', value: 20 }, 
      { label: 'label3', value: 30 }
    ];

    */

   var element = ('#'+domelementid);
  $(element)
  .append('<h6>'+title+'</h6>');

  $(element)
  .append('<div id="hoverbox'+domelementid+'"><p id="hoverboxkey'+domelementid+'"><strong>Important Label Heading</strong></p><p><span id="hoverboxvalue'+domelementid+'">100</span>%</p></div>')
  .find('svg')
  .first()
  .css({

    'display': 'block',
    'margin-left': 'auto',
    'margin-right': 'auto'    

  });

  $(element).find('#hoverbox'+domelementid)
  .css({

    'position': 'absolute',
    'width': '200px',
    'height': 'auto',
    'padding': '10px',
    'background-color': 'white',
    '-webkit-border-radius': '10px',
    '-moz-border-radius': '10px',
    'border-radius': '10px',
    '-webkit-box-shadow': '4px 4px 10px rgba(0, 0, 0, 0.4)',
    '-mox-box-shadow': '4px 4px 4px 10px rgba(0, 0, 0, 0.4)',
    'box-shadow': '4px 4px 10px rbga(0, 0, 0, 0.4) pointer-events: none',
    'display': 'none'

  }).find('p')
  .css({

    'margin': '0',
    'font-family': 'sans-serif',
    'font-size': '16px',
    'line-height': '20px'

  })

  
  var total = 0;

    for(var i = 0; i<dataset.length; i++ ){

      total += dataset[i].value;

    }

  var data = dataset;

  var margin = {top: 20, right: 20, bottom: 30, left: 40},
      width = parseInt(d3.select('#'+domelementid).style('width')),
      height = width - margin.top - margin.bottom;

  var x = d3.scale.ordinal()
      .rangeRoundBands([0, width], .1);

  var y = d3.scale.linear()
      .range([height, 0]);

  var xAxis = d3.svg.axis()
      .scale(x)
      .orient("bottom");

  var yAxis = d3.svg.axis()
      .scale(y)
      .orient("left")
      .ticks(5);

  var svg = d3.select("#"+domelementid).append("svg")
      .attr("width", width + margin.left + margin.right)
      .attr("height", height + margin.top + margin.bottom)
    .append("g")
      .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

  

    x.domain(data.map(function(d) { return d.label; }));
    y.domain([0, d3.max(data, function(d) { return d.value; })]);

    svg.append("g")
        .attr("class", "x axis")
        .attr("transform", "translate(0," + height + ")")
        .call(xAxis);

    svg.append("g")
        .attr("class", "y axis")
        .call(yAxis)
      .append("text")
        .attr("transform", "rotate(-90)")
        .attr("y", 6)
        .attr("dy", ".71em")
        .style("text-anchor", "end")
        .text("Value");

    svg.selectAll(".bar")
        .data(data)
      .enter().append("rect")
        .attr("class", "bar")
        .attr("x", function(d) { return x(d.label); })
        .attr("width", x.rangeBand())
        .attr("y", function(d) { return y(d.value); })
        .attr("height", function(d) { return height - y(d.value); })

         .on("mouseenter", function (d) {

        var offset = $('#'+domelementid).offset();
       
        console.log('mouseenter trggeredd');
        d3.select("#hoverbox"+domelementid)
          
          .style("left",  (d3.event.pageX - offset.left + 20)+'px' ) 
          .style("top", (d3.event.pageY - offset.top)+"px")   
          .style("display", "block")
          .style("z-index", "9999")
          .attr("pointer-events", "none")
          .select('#hoverboxkey'+domelementid)
          .text(function(){
            
              d3.select('#hoverboxvalue'+domelementid)
              .text(function(){
              
                return (d.value/total)*100;
              });


            return d.label;
          });
          
      })
      .on("mouseout", function () { 

        console.log('mouseout trggeredd');
        d3.select("#hoverbox"+domelementid)
          .style("display", "none")
          .attr("pointer-events", "none");
    });
 

  function type(d) {
    d.value = +d.value;
    return d;
  }



}