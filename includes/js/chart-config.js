// Chart.js configuration and helper functions

// Common chart configuration
const chartDefaults = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: true,
      position: 'top',
    },
    tooltip: {
      enabled: true,
      mode: 'index',
      intersect: false,
    }
  },
  scales: {
    x: {
      grid: {
        display: true,
        color: '#f0f0f0'
      }
    },
    y: {
      grid: {
        display: true,
        color: '#f0f0f0'
      }
    }
  }
};

// Color palette
const chartColors = {
  primary: '#DC2626',
  success: '#10B981',
  warning: '#F59E0B',
  info: '#3B82F6',
  light: '#F3F4F6',
  dark: '#374151'
};

// Helper function to create line chart
function createLineChart(canvasId, data, options = {}) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return null;

  const config = {
    type: 'line',
    data: data,
    options: {
      ...chartDefaults,
      ...options
    }
  };

  return new Chart(ctx, config);
}

// Helper function to create bar chart
function createBarChart(canvasId, data, options = {}) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return null;

  const config = {
    type: 'bar',
    data: data,
    options: {
      ...chartDefaults,
      ...options
    }
  };

  return new Chart(ctx, config);
}

// Helper function to create area chart
function createAreaChart(canvasId, data, options = {}) {
  const ctx = document.getElementById(canvasId);
  if (!ctx) return null;

  const config = {
    type: 'line',
    data: {
      ...data,
      datasets: data.datasets.map(dataset => ({
        ...dataset,
        fill: true,
        tension: 0.4
      }))
    },
    options: {
      ...chartDefaults,
      ...options
    }
  };

  return new Chart(ctx, config);
}
