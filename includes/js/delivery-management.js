/**
 * Delivery Management JavaScript
 * Handles all delivery-related functionality
 */

// Make sure SweetAlert2 is available
if (typeof Swal === 'undefined') {
  console.error('SweetAlert2 is required for this script to work. Please include it in your HTML.');
}

document.addEventListener('DOMContentLoaded', function() {
  
  // Global variables
  let currentPage = 1;
  let totalPages = 1;
  
  /**
   * Load deliveries with pagination and filters
   */
  window.loadDeliveries = function(page = 1, filters = {}) {
    currentPage = page;
    const deliveryTable = document.querySelector('#product-delivery .data-table tbody');
    const paginationContainer = document.getElementById('deliveries-pagination');
    
    if (!deliveryTable) return;
    
    // Show loading state
    deliveryTable.innerHTML = `
      <tr>
        <td colspan="9" style="text-align: center; padding: 30px;">
          <div style="display: inline-block;">
            <i class="fas fa-circle-notch fa-spin" style="font-size: 24px; color: var(--primary-red);"></i>
            <p>Loading deliveries...</p>
          </div>
        </td>
      </tr>
    `;
    
    // Build query string
    const params = new URLSearchParams({
      page: page,
      limit: 10,
      ...filters
    });
    
    fetch(`../../includes/handlers/get_deliveries.php?${params}`)
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          totalPages = data.totalPages || 1;
          
          if (data.deliveries && data.deliveries.length > 0) {
            deliveryTable.innerHTML = '';
            
            // Render each delivery row
            data.deliveries.forEach(delivery => {
              const row = createDeliveryRow(delivery);
              deliveryTable.appendChild(row);
            });
            
            // Update totals if available
            if (data.totals) {
              updateDeliveryTotals(data.totals);
            }
            
            // Create pagination
            if (paginationContainer) {
              renderPagination(paginationContainer, currentPage, totalPages);
            }
          } else {
            deliveryTable.innerHTML = `
              <tr>
                <td colspan="9" style="text-align: center; padding: 30px;">
                  <i class="fas fa-truck" style="font-size: 24px; color: var(--text-light); margin-bottom: 10px;"></i>
                  <p>No deliveries found matching your criteria.</p>
                  <button class="btn btn-outline" onclick="clearFilters()">Clear Filters</button>
                </td>
              </tr>
            `;
            
            // Empty pagination
            if (paginationContainer) {
              paginationContainer.innerHTML = '';
            }
          }
        } else {
          deliveryTable.innerHTML = `
            <tr>
              <td colspan="9" style="text-align:center;">Error loading deliveries: ${data.message || 'Unknown error'}</td>
            </tr>
          `;
          
          // Empty pagination
          if (paginationContainer) {
            paginationContainer.innerHTML = '';
          }
        }
      })
      .catch(error => {
        console.error('Error fetching deliveries:', error);
        deliveryTable.innerHTML = `
          <tr>
            <td colspan="9" style="text-align:center;">Error loading deliveries. Please try again.</td>
          </tr>
        `;
        
        // Empty pagination
        if (paginationContainer) {
          paginationContainer.innerHTML = '';
        }
      });
  };
  
  /**
   * Create a table row for a delivery record
   */
  function createDeliveryRow(delivery) {
    const deliveryDate = new Date(delivery.delivery_date).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    });
    
    const formattedPrice = new Intl.NumberFormat('en-PH', {
      style: 'currency',
      currency: 'PHP',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(delivery.unit_price || 0);
    
    const formattedTotal = new Intl.NumberFormat('en-PH', {
      style: 'currency',
      currency: 'PHP',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(delivery.total_value || 0);
    
    // Define status class
    let statusClass = 'status-pending';
    if (delivery.status === 'completed') {
      statusClass = 'status-completed';
    } else if (delivery.status === 'canceled') {
      statusClass = 'status-canceled';
    }
    
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${deliveryDate}</td>
      <td><span class="reference-tag">${delivery.delivery_reference || 'N/A'}</span></td>
      <td>${delivery.model_name || ''} ${delivery.variant ? '<br><small>' + delivery.variant + '</small>' : ''}</td>
      <td>${delivery.supplier_dealer || ''}</td>
      <td>${delivery.units_delivered || 0}</td>
      <td>${formattedPrice}</td>
      <td>${formattedTotal}</td>
      <td><span class="delivery-status ${statusClass}">${delivery.status || 'pending'}</span></td>
      <td class="table-actions">
        <button class="btn btn-small btn-outline" onclick="editDelivery(${delivery.id})">
          <i class="fas fa-edit"></i> Edit
        </button>
        <button class="btn btn-small btn-secondary" onclick="deleteDelivery(${delivery.id})">
          <i class="fas fa-trash"></i> Delete
        </button>
      </td>
    `;
    
    return row;
  }
  
  /**
   * Update delivery summary cards
   */
  function updateDeliveryTotals(totals) {
    const totalUnitsCard = document.querySelector('#delivery-total-units');
    const totalValueCard = document.querySelector('#delivery-total-value');
    const deliveryDaysCard = document.querySelector('#delivery-days');
    const totalDeliveriesCard = document.querySelector('#total-deliveries');
    
    if (totalUnitsCard) totalUnitsCard.textContent = totals.total_units || 0;
    
    if (totalValueCard) {
      const formattedValue = new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
        notation: 'compact',
        compactDisplay: 'short'
      }).format(totals.total_value || 0);
      
      totalValueCard.textContent = formattedValue;
    }
    
    if (deliveryDaysCard) deliveryDaysCard.textContent = totals.delivery_days || 0;
    if (totalDeliveriesCard) totalDeliveriesCard.textContent = totals.total_deliveries || 0;
  }
  
  /**
   * Render pagination controls
   */
  function renderPagination(container, currentPage, totalPages) {
    container.innerHTML = '';
    
    if (totalPages <= 1) return;
    
    // Previous button
    const prevBtn = document.createElement('button');
    prevBtn.classList.add('pagination-btn');
    prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
    prevBtn.disabled = currentPage === 1;
    prevBtn.addEventListener('click', () => loadDeliveries(currentPage - 1, getDeliveryFilters()));
    container.appendChild(prevBtn);
    
    // Page number buttons
    const maxButtons = 5;
    const startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
    const endPage = Math.min(totalPages, startPage + maxButtons - 1);
    
    for (let i = startPage; i <= endPage; i++) {
      const pageBtn = document.createElement('button');
      pageBtn.classList.add('pagination-btn');
      if (i === currentPage) pageBtn.classList.add('active');
      pageBtn.textContent = i;
      pageBtn.addEventListener('click', () => loadDeliveries(i, getDeliveryFilters()));
      container.appendChild(pageBtn);
    }
    
    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.classList.add('pagination-btn');
    nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
    nextBtn.disabled = currentPage === totalPages;
    nextBtn.addEventListener('click', () => loadDeliveries(currentPage + 1, getDeliveryFilters()));
    container.appendChild(nextBtn);
  }
  
  /**
   * Edit delivery record
   */
  window.editDelivery = function(id) {
    // Show loading
    Swal.fire({
      title: 'Loading Delivery Details',
      text: 'Please wait...',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });
    
    // Fetch delivery data
    fetch(`../../includes/handlers/get_delivery.php?id=${id}`)
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          const delivery = data.delivery;
          
          // Show edit form
          const editFormHtml = createEditDeliveryForm(delivery);
          
          Swal.fire({
            title: 'Edit Delivery Record',
            html: editFormHtml,
            width: '800px',
            showCloseButton: true,
            showCancelButton: true,
            confirmButtonText: 'Save Changes',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#28a745',
            focusConfirm: false,
            didOpen: () => {
              // Set form values
              document.getElementById('edit-delivery-date').value = delivery.delivery_date;
              document.getElementById('edit-supplier').value = delivery.supplier_dealer;
              document.getElementById('edit-model').value = delivery.model_name;
              document.getElementById('edit-variant').value = delivery.variant;
              document.getElementById('edit-color').value = delivery.color;
              document.getElementById('edit-units').value = delivery.units_delivered;
              document.getElementById('edit-unit-price').value = delivery.unit_price;
              document.getElementById('edit-total-value').value = delivery.total_value;
              document.getElementById('edit-status').value = delivery.status;
              document.getElementById('edit-notes').value = delivery.delivery_notes;
              
              // Calculate total when units or price changes
              const unitsInput = document.getElementById('edit-units');
              const priceInput = document.getElementById('edit-unit-price');
              const totalInput = document.getElementById('edit-total-value');
              
              function updateTotal() {
                const units = parseFloat(unitsInput.value) || 0;
                const price = parseFloat(priceInput.value) || 0;
                totalInput.value = (units * price).toFixed(2);
              }
              
              unitsInput.addEventListener('input', updateTotal);
              priceInput.addEventListener('input', updateTotal);
            },
            preConfirm: () => {
              // Validate form
              const editForm = document.getElementById('edit-delivery-form');
              if (!editForm.checkValidity()) {
                editForm.reportValidity();
                return false;
              }
              
              // Get values
              return {
                id: delivery.id,
                delivery_date: document.getElementById('edit-delivery-date').value,
                supplier_dealer: document.getElementById('edit-supplier').value,
                model_name: document.getElementById('edit-model').value,
                variant: document.getElementById('edit-variant').value,
                color: document.getElementById('edit-color').value,
                units_delivered: document.getElementById('edit-units').value,
                unit_price: document.getElementById('edit-unit-price').value,
                delivery_notes: document.getElementById('edit-notes').value,
                status: document.getElementById('edit-status').value
              };
            }
          }).then((result) => {
            if (result.isConfirmed) {
              updateDelivery(result.value);
            }
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: data.message || 'Failed to load delivery details'
          });
        }
      })
      .catch(error => {
        console.error('Error:', error);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Failed to load delivery details'
        });
      });
  };
  
  /**
   * Create the edit delivery form HTML
   */
  function createEditDeliveryForm(delivery) {
    return `
      <form id="edit-delivery-form" class="swal2-form">
        <div style="max-height: 60vh; overflow-y: auto; padding-right: 10px;">
          <!-- Delivery details -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <div>
              <label for="edit-delivery-date" style="display: block; margin-bottom: 5px; font-weight: 600;">Delivery Date</label>
              <input type="date" id="edit-delivery-date" class="swal2-input" style="margin: 0;" required>
            </div>
            <div>
              <label for="edit-supplier" style="display: block; margin-bottom: 5px; font-weight: 600;">Supplier/Dealer</label>
              <input type="text" id="edit-supplier" class="swal2-input" style="margin: 0;" required>
            </div>
          </div>
          
          <!-- Vehicle details -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <div>
              <label for="edit-model" style="display: block; margin-bottom: 5px; font-weight: 600;">Model Name</label>
              <input type="text" id="edit-model" class="swal2-input" style="margin: 0;" required>
            </div>
            <div>
              <label for="edit-variant" style="display: block; margin-bottom: 5px; font-weight: 600;">Variant</label>
              <input type="text" id="edit-variant" class="swal2-input" style="margin: 0;" required>
            </div>
          </div>
          
          <!-- Color and Units -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <div>
              <label for="edit-color" style="display: block; margin-bottom: 5px; font-weight: 600;">Color</label>
              <input type="text" id="edit-color" class="swal2-input" style="margin: 0;" required>
            </div>
            <div>
              <label for="edit-units" style="display: block; margin-bottom: 5px; font-weight: 600;">Units Delivered</label>
              <input type="number" id="edit-units" class="swal2-input" style="margin: 0;" min="1" required>
            </div>
          </div>
          
          <!-- Price and Status -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
            <div>
              <label for="edit-unit-price" style="display: block; margin-bottom: 5px; font-weight: 600;">Unit Price (₱)</label>
              <input type="number" id="edit-unit-price" class="swal2-input" style="margin: 0;" min="0" step="0.01" required>
            </div>
            <div>
              <label for="edit-total-value" style="display: block; margin-bottom: 5px; font-weight: 600;">Total Value (₱)</label>
              <input type="number" id="edit-total-value" class="swal2-input" style="margin: 0;" readonly>
            </div>
          </div>
          
          <div style="display: grid; grid-template-columns: 1fr; gap: 15px; margin-bottom: 15px;">
            <div>
              <label for="edit-status" style="display: block; margin-bottom: 5px; font-weight: 600;">Status</label>
              <select id="edit-status" class="swal2-select" style="width: 100%; margin: 0;">
                <option value="completed">Completed</option>
                <option value="pending">Pending</option>
                <option value="canceled">Canceled</option>
              </select>
            </div>
          </div>
          
          <!-- Notes -->
          <div style="margin-bottom: 15px;">
            <label for="edit-notes" style="display: block; margin-bottom: 5px; font-weight: 600;">Delivery Notes</label>
            <textarea id="edit-notes" class="swal2-textarea" style="margin: 0; height: 80px;"></textarea>
          </div>
        </div>
      </form>
    `;
  }
  
  /**
   * Submit updated delivery data to server
   */
  function updateDelivery(data) {
    Swal.fire({
      title: 'Updating Delivery...',
      text: 'Please wait...',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });
    
    // Create form data
    const formData = new FormData();
    for (const key in data) {
      formData.append(key, data[key]);
    }
    
    // Send to server
    fetch('../../includes/handlers/update_delivery.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success') {
        Swal.fire({
          icon: 'success',
          title: 'Delivery Updated!',
          text: 'The delivery record has been updated successfully.',
          timer: 2000,
          timerProgressBar: true,
          toast: true,
          position: 'top-end',
          showConfirmButton: false
        }).then(() => {
          loadDeliveries(currentPage, getDeliveryFilters());
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Update Failed',
          text: data.message || 'Failed to update delivery record'
        });
      }
    })
    .catch(error => {
      console.error('Error:', error);
      Swal.fire({
        icon: 'error',
        title: 'Connection Error',
        text: 'Failed to connect to the server. Please try again.'
      });
    });
  }
  
  /**
   * Delete a delivery record
   */
  window.deleteDelivery = function(id) {
    Swal.fire({
      title: 'Delete Delivery',
      text: 'Are you sure you want to delete this delivery record? This action cannot be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, delete it!',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        // Show loading
        Swal.fire({
          title: 'Deleting...',
          text: 'Please wait...',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });
        
        // Send delete request
        fetch('../../includes/handlers/delete_delivery.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: 'The delivery record has been deleted.',
              timer: 2000,
              timerProgressBar: true,
              toast: true,
              position: 'top-end',
              showConfirmButton: false
            }).then(() => {
              // Reload the current page of deliveries
              loadDeliveries(currentPage, getDeliveryFilters());
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Delete Failed',
              text: data.message || 'Failed to delete the delivery record'
            });
          }
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Connection Error',
            text: 'Failed to connect to the server. Please try again.'
          });
        });
      }
    });
  };
  
  /**
   * Clear all filters and reload deliveries
   */
  window.clearFilters = function() {
    // Reset search field
    const searchInput = document.getElementById('delivery-search');
    if (searchInput) searchInput.value = '';
    
    // Reset date fields
    const startDateInput = document.getElementById('delivery-start-date');
    const endDateInput = document.getElementById('delivery-end-date');
    if (startDateInput) startDateInput.value = '';
    if (endDateInput) endDateInput.value = '';
    
    // Reset status filter
    const statusFilter = document.getElementById('status-filter');
    if (statusFilter) statusFilter.value = '';
    
    // Reset advanced filters
    const modelFilter = document.getElementById('filter-model');
    const supplierFilter = document.getElementById('filter-supplier');
    const receivedByFilter = document.getElementById('filter-received-by');
    const sortFilter = document.getElementById('filter-sort');
    
    if (modelFilter) modelFilter.value = '';
    if (supplierFilter) supplierFilter.value = '';
    if (receivedByFilter) receivedByFilter.value = '';
    if (sortFilter) sortFilter.value = 'delivery_date|desc';
    
    // Load deliveries with no filters
    loadDeliveries(1, {});
  };
  
  // Initialize if we're on the delivery page
  if (document.getElementById('product-delivery') && 
      document.getElementById('product-delivery').classList.contains('active')) {
    loadDeliveries();
  }
});
