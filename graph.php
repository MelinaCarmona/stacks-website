<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <style>
      .named, .unnamed {
        stroke-width: 1.5px;
      }
      
      .named {
        stroke: #777;
      }
      
      .unnamed {
        stroke: #fff;
      }
      
      .link {
        stroke: #999;
        stroke-opacity: .6;
      }
      
      .tooltip p {
        font-size: .9em;
        border-radius: 5px;
        border: 1px solid black;
        background-color: white;
        padding: 2px;
      }
    </style>
    <script src="http://d3js.org/d3.v3.min.js"></script>
    <script src="http://code.jquery.com/jquery-1.9.1.js"></script>
    <script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
  </head>
  <body>
    <script>
      var width = 1800,
          height = 1000;
      
      var force = d3.layout.force()
          .charge(-500)
          .linkDistance(10)
          .gravity(.5)
          .size([width, height]);
      
      var svg = d3.select("body").append("svg")
          .attr("width", width)
          .attr("height", height);
      
      d3.json("<?php print $_GET["tag"]; ?>-force.json", function(error, graph) {
        force
          .nodes(graph.nodes) 
          .links(graph.links)
          .start();
      
        var link = svg.selectAll(".link")
          .data(graph.links)
          .enter().append("line")
          .attr("class", "link")
          .style("stroke-width", function(d) { return Math.sqrt(d.value); });
      
        var type_map = {
          "definition": d3.rgb("green"),
          "remark": d3.rgb("black"),
          "item": d3.rgb("yellow"),
          "section": d3.rgb("red"),
          "lemma": d3.rgb("orange"),
          "proposition": d3.rgb("blue"),
          "theorem": d3.rgb("purple"),
          "example": d3.rgb("grey"),
        }
      
        function displayInfo(node) {
          // element exists, so we show it, while updating its position
          if ($("#" + node.tag + "-tooltip").length) {
            $("#" + node.tag + "-tooltip").css({top: node.y - 10 + "px", left: node.x + 20 + "px"})
              .fadeIn(100);
          }
          // otherwise we create a new tooltip
          else {
            var tooltipContent = $("<p>")
              .append("tag " + node.tag)
              .append("<br>" + node.type);
      
            var tooltip = $("<div>", {class: "tooltip", id: node.tag + "-tooltip"})
              .append(tooltipContent)
              .css({position: "absolute", top: node.y - 10 + "px", left: node.x + 20 + "px"});
      
            $('body').append(tooltip);
          }
        }
      
        function hideInfo(node) {
          $("#" + node.tag + "-tooltip").fadeOut(200);
        }

        function openTag(node) {
          window.open("graph.php?tag=" + node.tag);
        }
      
        var node = svg.selectAll(".node")
          .data(graph.nodes)
          .enter().append("circle")
          .attr("class", function(d) { if (typeof d.tag == "undefined") { return "named"; } else { return "unnamed"; } })
          .attr("r", function(d) { return 4*Math.pow(parseInt(d.size)+1, 1/3); }) // control the size
          .style("fill", function(d) { return type_map[d.type]; }) // control the color
          .on("mouseover", displayInfo)
          .on("mouseout", hideInfo)
          .on("click", openTag)
          .call(force.drag);
      
        force.on("tick", function() {
          link
            .attr("x1", function(d) { return d.source.x; })
            .attr("y1", function(d) { return d.source.y; })
            .attr("x2", function(d) { return d.target.x; })
            .attr("y2", function(d) { return d.target.y; });
           
          node
            .attr("cx", function(d) { return d.x; })
            .attr("cy", function(d) { return d.y; });
        });
      });
    </script>
  </body>
</html>
