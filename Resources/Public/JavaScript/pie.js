// Create the Pie Elements (SVG and Circle)
function createElements(pie) {
  const NS = "http://www.w3.org/2000/svg";
  const svg = document.createElementNS(NS, "svg");
  const circle = document.createElementNS(NS, "circle");

  circle.setAttribute("r", 16);
  circle.setAttribute("cx", 16);
  circle.setAttribute("cy", 16);
  circle.setAttribute("stroke-dasharray", pie.dataset.currentUsageRate + " 100");

  svg.setAttribute("viewBox", "0 0 32 32");
  svg.setAttribute("aria-hidden", "true");
  svg.appendChild(circle);
  pie.appendChild(svg);
}

// Iterate all storage elements
document.querySelectorAll('.pie').forEach(function (pie) {
  createElements(pie);
});
