let currentPieData = null;
let currentColumnData = null;
let currentTrendData = null;

let isGoogleChartsReady = false;
const googleChartsPromise = new Promise((resolve) => {
    if (typeof google !== "undefined" && google.charts) {
        google.charts.load("current", { packages: ["corechart"] });
        google.charts.setOnLoadCallback(() => {
            isGoogleChartsReady = true;
            resolve();
        });
    } else {
        resolve();
    }
});

let summaryController = null;
let pieController = null;
let columnController = null;
let trendController = null;

function initDashboard() {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, "0");
    const dd = String(today.getDate()).padStart(2, "0");

    const dailyDate = document.getElementById("dailyDate");
    const monthlyDate = document.getElementById("monthlyDate");

    if (dailyDate && !dailyDate.value) {
        dailyDate.value = `${yyyy}-${mm}-${dd}`;
    }

    if (monthlyDate && !monthlyDate.value) {
        monthlyDate.value = `${yyyy}-${mm}`;
    }

    updateFilterInputs();
}

document.addEventListener("DOMContentLoaded", () => {
    initDashboard();

    const mobileToggleBtn = document.getElementById("mobileToggleBtn");
    const sidebar = document.getElementById("sidebar");
    const sidebarOverlay = document.getElementById("sidebarOverlay");

    function closeSidebar() {
        if (sidebar && sidebarOverlay) {
            sidebar.classList.remove("active");
            sidebarOverlay.classList.remove("active");
        }
    }

    if (mobileToggleBtn && sidebar && sidebarOverlay) {
        mobileToggleBtn.addEventListener("click", () => {
            if (sidebar.classList.contains("active")) {
                closeSidebar();
            } else {
                sidebar.classList.add("active");
                sidebarOverlay.classList.add("active");
            }
        });

        sidebarOverlay.addEventListener("click", closeSidebar);

        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && sidebar.classList.contains("active")) {
                closeSidebar();
            }
        });
    }

    const productFilter = document.getElementById("productFilter");

    if (productFilter) {
        productFilter.addEventListener("change", function () {
            const productID = this.value;
            if (productID) {
                fetchSalesTrend(productID);
            }
        });
    }

    fetch("php/get_categories.php")
        .then((response) => response.json())
        .then((data) => {
            const categoryFilter = document.getElementById("categoryFilter");
            if (categoryFilter) {
                data.forEach((item) => {
                    const option = document.createElement("option");
                    option.value = item.category;
                    option.textContent = item.category;
                    categoryFilter.appendChild(option);
                });
            }
        })
        .catch((error) => console.error("Error fetching categories:", error));
});

let resizeTimer;

window.addEventListener("resize", () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
        requestAnimationFrame(() => {
            if (currentPieData) drawChart(currentPieData);
            if (currentColumnData) drawColumnChart(currentColumnData);
            if (currentTrendData) drawSalesTrendChart(currentTrendData);
        });
    }, 150);
});

function updateFilterInputs() {
    const filterTypeElem = document.getElementById("filterType");
    if (!filterTypeElem) return;

    const filterType = filterTypeElem.value;

    const dailyDate = document.getElementById("dailyDate");
    const dailyLabel = document.getElementById("dailyDateLabel");

    if (dailyDate) {
        dailyDate.style.display = filterType === "daily" ? "inline-block" : "none";
    }

    if (dailyLabel) {
        dailyLabel.style.display = filterType === "daily" ? "flex" : "none";
    }

    const monthlyDate = document.getElementById("monthlyDate");
    const monthlyLabel = document.getElementById("monthlyDateLabel");

    if (monthlyDate) {
        monthlyDate.style.display = filterType === "monthly" ? "inline-block" : "none";
    }

    if (monthlyLabel) {
        monthlyLabel.style.display = filterType === "monthly" ? "flex" : "none";
    }

    fetchSummaryData();
    fetchChartData();
    fetchColumnChartData();
}

function fetchSummaryData() {
    const filterTypeElem = document.getElementById("filterType");
    if (!filterTypeElem) return;

    if (summaryController) summaryController.abort();
    summaryController = new AbortController();

    const filterType = filterTypeElem.value;
    let url = "php/data_summary.php";

    if (filterType === "daily") {
        const dailyDate = document.getElementById("dailyDate").value;
        url += `?filter=daily&date=${dailyDate}`;
    } else if (filterType === "monthly") {
        const monthlyDate = document.getElementById("monthlyDate").value;
        url += `?filter=monthly&month=${monthlyDate}`;
    }

    fetch(url, { signal: summaryController.signal })
        .then((response) => response.json())
        .then((data) => updateSummaryCards(data))
        .catch((error) => {
            if (error.name !== "AbortError") console.error("Error fetching data:", error);
        });
}

function updateSummaryCards(data) {
    const totalSalesValue = document.getElementById("totalSalesValue");
    const totalUnitsValue = document.getElementById("totalUnitsValue");
    const newMembersValue = document.getElementById("newMembersValue");
    const stockInValue = document.getElementById("stockInValue");

    if (totalSalesValue) {
        totalSalesValue.textContent = parseFloat(data.total_sales || 0).toFixed(2);
    }

    if (totalUnitsValue) {
        totalUnitsValue.textContent = data.total_units || 0;
    }

    if (newMembersValue) {
        newMembersValue.textContent = data.new_members || 0;
    }

    if (stockInValue) {
        stockInValue.textContent = data.stock_in || 0;
    }
}

function fetchChartData() {
    const filterTypeElem = document.getElementById("filterType");
    if (!filterTypeElem) return;

    if (pieController) pieController.abort();
    pieController = new AbortController();

    const filterType = filterTypeElem.value;
    let url = "php/payment_method_data.php";

    if (filterType === "daily") {
        const dailyDate = document.getElementById("dailyDate").value;
        url += `?filter=daily&date=${dailyDate}`;
    } else if (filterType === "monthly") {
        const monthlyDate = document.getElementById("monthlyDate").value;
        url += `?filter=monthly&month=${monthlyDate}`;
    }

    const dataPromise = fetch(url, { signal: pieController.signal }).then((response) => response.json());

    const readyPromise = isGoogleChartsReady ? Promise.resolve() : googleChartsPromise;

    Promise.all([dataPromise, readyPromise])
        .then(([data]) => {
            currentPieData = data;
            requestAnimationFrame(() => drawChart(data));
        })
        .catch((error) => {
            if (error.name !== "AbortError") console.error("Error fetching data:", error);
        });
}

function drawChart(chartData) {
    const pieElem = document.getElementById("piechart");
    if (!pieElem) return;

    if (!chartData || chartData.length === 0) {
        pieElem.innerHTML = `
            <div class="empty-chart-msg"><i class="fa-solid fa-chart-pie"></i><p>No payment data available</p></div>`;
        return;
    }

    const data = new google.visualization.DataTable();
    data.addColumn("string", "Payment Method");
    data.addColumn("number", "Count");
    data.addRows(chartData);

    const options = {
        pieHole: 0.45,
        colors: [
            "#3f6844",
            "#14b8a6",
            "#3b82f6",
            "#f97316",
            "#8b5cf6",
        ],
        backgroundColor: "transparent",
        fontName: "Outfit",
        chartArea: {
            width: "90%",
            height: "80%",
        },
        legend: {
            position: "bottom",
            textStyle: {
                fontSize: 12,
                color: "#64748b",
            },
        },
        pieSliceTextStyle: {
            fontSize: 12,
            fontWeight: "bold",
        },
    };

    const chart = new google.visualization.PieChart(pieElem);
    chart.draw(data, options);
}

function fetchColumnChartData() {
    const filterTypeElem = document.getElementById("filterType");
    if (!filterTypeElem) return;

    if (columnController) columnController.abort();
    columnController = new AbortController();

    const filterType = filterTypeElem.value;
    let url = "php/top_products_data.php";

    if (filterType === "daily") {
        const dailyDate = document.getElementById("dailyDate").value;
        url += `?filter=daily&date=${dailyDate}`;
    } else if (filterType === "monthly") {
        const monthlyDate = document.getElementById("monthlyDate").value;
        url += `?filter=monthly&month=${monthlyDate}`;
    } else if (filterType === "overall") {
        url += "?filter=overall";
    }

    const dataPromise = fetch(url, { signal: columnController.signal }).then((response) => response.json());

    const readyPromise = isGoogleChartsReady ? Promise.resolve() : googleChartsPromise;

    Promise.all([dataPromise, readyPromise])
        .then(([data]) => {
            currentColumnData = data;
            requestAnimationFrame(() => drawColumnChart(data));
        })
        .catch((error) => {
            if (error.name !== "AbortError") console.error("Error fetching data:", error);
        });
}

function drawColumnChart(chartData) {
    const colElem = document.getElementById("columnchart_values");
    if (!colElem) return;

    if (!chartData || chartData.length === 0) {
        colElem.innerHTML = `
            <div class="empty-chart-msg"><i class="fa-solid fa-chart-column"></i><p>No sales data available</p></div>`;
        return;
    }

    const barColors = [
        "#3f6844",
        "#14b8a6",
        "#3b82f6",
        "#8b5cf6",
        "#f97316",
    ];

    const data = new google.visualization.DataTable();
    data.addColumn("string", "Product");
    data.addColumn("number", "Units Sold");
    data.addColumn({
        type: "string",
        role: "style",
    });

    chartData.forEach((product, index) => {
        const color = product.color || barColors[index % barColors.length];
        data.addRow([
            product.product_name,
            parseInt(product.units_sold),
            `color: ${color}`,
        ]);
    });

    const options = {
        fontName: "Outfit",
        chartArea: {
            width: "85%",
            height: "70%",
        },
        animation: {
            startup: true,
            duration: 300,
            easing: "out",
        },
        hAxis: {
            textStyle: {
                fontSize: 11,
                color: "#64748b",
            },
        },
        vAxis: {
            title: "Units Sold",
            titleTextStyle: {
                fontSize: 12,
                color: "#475569",
                italic: false,
            },
            textStyle: {
                fontSize: 11,
                color: "#64748b",
            },
            gridlines: {
                color: "#f1f5f9",
            },
            baselineColor: "#cbd5e1",
        },
        backgroundColor: "transparent",
        legend: {
            position: "none",
        },
        bar: {
            groupWidth: "55%",
        },
    };

    const chart = new google.visualization.ColumnChart(colElem);
    chart.draw(data, options);
}

function fetchSalesTrend(productID) {
    if (trendController) trendController.abort();
    trendController = new AbortController();

    const dataPromise = fetch(`php/get_sales_trend.php?productID=${productID}`, { signal: trendController.signal }).then((response) => response.json());

    const readyPromise = isGoogleChartsReady ? Promise.resolve() : googleChartsPromise;

    Promise.all([dataPromise, readyPromise])
        .then(([data]) => {
            currentTrendData = data;
            requestAnimationFrame(() => drawSalesTrendChart(data));
        })
        .catch((error) => {
            if (error.name !== "AbortError") console.error("Error fetching sales trend:", error);
        });
}

function drawSalesTrendChart(salesData) {
    const chartDiv = document.getElementById("salesTrendvalues");
    if (!chartDiv) return;

    if (!salesData || salesData.length === 0 || salesData.error) {
        chartDiv.innerHTML = `
            <div class="empty-chart-msg"><i class="fa-solid fa-chart-line"></i><p>No sales history available for this product</p></div>`;

        if (salesData && salesData.error) {
            console.error(salesData.error);
        }
        return;
    }

    const data = new google.visualization.DataTable();
    data.addColumn("string", "Month");
    data.addColumn("number", "Sales ($)");

    salesData.forEach((item) => {
        const monthName = item.month_label;
        const sales = parseFloat(item.sales);

        if (!isNaN(sales) && monthName) {
            data.addRow([monthName, sales]);
        }
    });

    const options = {
        fontName: "Outfit",
        curveType: "function",
        lineWidth: 3,
        pointSize: 6,
        colors: ["#3f6844"],
        backgroundColor: "transparent",
        chartArea: {
            width: "90%",
            height: "75%",
        },
        animation: {
            startup: true,
            duration: 300,
            easing: "out",
        },
        crosshair: {
            trigger: "both",
            orientation: "vertical",
            color: "#3f6844",
            opacity: 0.3,
        },
        legend: {
            position: "none",
        },
        hAxis: {
            title: "Month",
            titleTextStyle: {
                fontSize: 12,
                color: "#475569",
                italic: false,
            },
            textStyle: {
                fontSize: 11,
                color: "#64748b",
            },
        },
        vAxis: {
            title: "Revenue ($)",
            titleTextStyle: {
                fontSize: 12,
                color: "#475569",
                italic: false,
            },
            textStyle: {
                fontSize: 11,
                color: "#64748b",
            },
            gridlines: {
                color: "#f1f5f9",
            },
            baselineColor: "#cbd5e1",
        },
    };

    const chart = new google.visualization.LineChart(chartDiv);
    chart.draw(data, options);
}

function updateProductFilter() {
    const selectedCategoryElem = document.getElementById("categoryFilter");
    const productFilter = document.getElementById("productFilter");

    if (!selectedCategoryElem || !productFilter) return;

    const selectedCategory = selectedCategoryElem.value;

    if (selectedCategory) {
        fetch(`php/get_products.php?category=${selectedCategory}`)
            .then((response) => response.json())
            .then((data) => {
                productFilter.innerHTML = '<option value="">Select Product</option>';
                data.forEach((item) => {
                    const option = document.createElement("option");
                    option.value = item.productID;
                    option.textContent = item.product_name;
                    productFilter.appendChild(option);
                });
            })
            .catch((error) => console.error("Error fetching products:", error));
    } else {
        productFilter.innerHTML = '<option value="">Select Product</option>';
    }
}