// Include SweetAlert2 CDN if not already loaded
if (!document.querySelector('script[src*="sweetalert2"]')) {
  const swalScript = document.createElement('script');
  swalScript.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
  document.head.appendChild(swalScript);
  
  // Also include the CSS
  const swalCSS = document.createElement('link');
  swalCSS.rel = 'stylesheet';
  swalCSS.href = 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css';
  document.head.appendChild(swalCSS);
}

// Admin-specific JavaScript functions
document.addEventListener('DOMContentLoaded', function() {
  // Delivery Management Functions - Make this a global function by adding to window
  window.loadDeliveries = function(page = 1, filters = {}) {
    const deliveryTable = document.querySelector('.data-table tbody');
    if (!deliveryTable) return;
    
    deliveryTable.innerHTML = '<tr><td colspan="9" style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Loading deliveries...</td></tr>';
    
    // Build query string
    const params = new URLSearchParams({
      page: page,
      ...filters
    });
    
    fetch(`../../includes/handlers/get_deliveries.php?${params}`)
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          if (data.deliveries && data.deliveries.length > 0) {
            deliveryTable.innerHTML = '';
            
            data.deliveries.forEach(delivery => {
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
              
              // Determine status class
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
              deliveryTable.appendChild(row);
            });
            
            // Update totals if available
            if (data.totals) {
              updateDeliveryTotals(data.totals);
            }
            
            // Update pagination if container exists
            const paginationContainer = document.getElementById('deliveries-pagination');
            if (paginationContainer) {
              renderPagination(paginationContainer, data.currentPage || 1, data.totalPages || 1, (page) => loadDeliveries(page, filters));
            }
          } else {
            deliveryTable.innerHTML = `
              <tr>
                <td colspan="9" style="text-align: center; padding: 30px;">
                  <i class="fas fa-truck" style="font-size: 24px; color: var(--text-light); margin-bottom: 10px;"></i>
                  <p>No deliveries found.</p>
                </td>
              </tr>
            `;
            
            // Clear pagination if no results
            const paginationContainer = document.getElementById('deliveries-pagination');
            if (paginationContainer) {
              paginationContainer.innerHTML = '';
            }
          }
        } else {
          deliveryTable.innerHTML = '<tr><td colspan="9" style="text-align:center;">Error loading deliveries. Please try again.</td></tr>';
        }
      })
      .catch(error => {
        console.error('Error fetching deliveries:', error);
        deliveryTable.innerHTML = '<tr><td colspan="9" style="text-align:center;">Error loading deliveries. Please try again.</td></tr>';
      });
  };
  
  // Helper function to render pagination for any table
  function renderPagination(container, currentPage, totalPages, callback) {
    container.innerHTML = '';
    
    if (totalPages <= 1) return;
    
    // Previous button
    const prevBtn = document.createElement('button');
    prevBtn.classList.add('pagination-btn');
    prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
    prevBtn.disabled = currentPage === 1;
    prevBtn.addEventListener('click', () => callback(currentPage - 1));
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
      pageBtn.addEventListener('click', () => callback(i));
      container.appendChild(pageBtn);
    }
    
    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.classList.add('pagination-btn');
    nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
    nextBtn.disabled = currentPage === totalPages;
    nextBtn.addEventListener('click', () => callback(currentPage + 1));
    container.appendChild(nextBtn);
  }
  
  function updateDeliveryTotals(totals) {
    // Update info cards with totals - only if elements exist
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
  
  window.editDelivery = function(id) {
    Swal.fire({
      title: 'Edit Delivery',
      text: 'Loading delivery details...',
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
          const editFormHtml = `
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
                total_value: document.getElementById('edit-total-value').value,
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
          loadDeliveries(1, getDeliveryFilters());
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
  
  window.deleteDelivery = function(id) {
    Swal.fire({
      title: 'Delete Delivery',
      text: 'Are you sure you want to delete this delivery record? Stock quantities will be adjusted.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, delete it!',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        Swal.fire({
          title: 'Deleting...',
          text: 'Removing delivery record...',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });
        
        fetch('../../includes/handlers/delete_delivery.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: 'Deleted!',
              text: 'Delivery record has been deleted.',
              timer: 2000,
              timerProgressBar: true,
              toast: true,
              position: 'top-end',
              showConfirmButton: false
            }).then(() => {
              loadDeliveries(); // Refresh the table
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Delete Failed',
              text: data.message || 'Failed to delete delivery record.',
              confirmButtonText: 'Try Again'
            });
          }
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Connection Error',
            text: 'Unable to connect to server. Please try again.',
            confirmButtonText: 'OK'
          });
        });
      }
    });
  };
  
  // Handle delivery form submission - only if the form exists
  const deliveryForm = document.getElementById('deliveryForm');
  if (deliveryForm) {
    deliveryForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      
      Swal.fire({
        title: 'Saving...',
        text: 'Recording delivery...',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });
      
      fetch('../../includes/handlers/delivery_handler.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: 'Delivery Recorded!',
            html: `
              <p>Delivery has been successfully recorded.</p>
              <p><strong>Reference:</strong> ${data.delivery_reference}</p>
              <p><strong>Total Value:</strong> ₱${new Intl.NumberFormat('en-PH').format(data.total_value)}</p>
            `,
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false
          }).then(() => {
            deliveryForm.reset();
            loadDeliveries(); // Refresh the table
          });
        } else {
          let errorMessage = data.message || 'Failed to record delivery.';
          if (data.errors && Array.isArray(data.errors)) {
            errorMessage = data.errors.join('<br>');
          }
          
          Swal.fire({
            icon: 'error',
            title: 'Recording Failed',
            html: errorMessage,
            confirmButtonText: 'Try Again'
          });
        }
      })
      .catch(error => {
        console.error('Error:', error);
        Swal.fire({
          icon: 'error',
          title: 'Connection Error',
          text: 'Unable to connect to server. Please try again.',
          confirmButtonText: 'OK'
        });
      });
    });
  }
  
  // Update vehicle variants when model is selected - only if both elements exist
  const modelSelect = document.querySelector('select[name="model_name"]');
  const variantSelect = document.querySelector('select[name="variant"]');
  
  if (modelSelect && variantSelect) {
    modelSelect.addEventListener('change', function() {
      const selectedModel = this.value;
      
      if (!selectedModel) {
        variantSelect.innerHTML = '<option value="">Select variant</option>';
        return;
      }
      
      // Show loading state
      variantSelect.innerHTML = '<option value="">Loading variants...</option>';
      variantSelect.disabled = true;
      
      fetch(`../../includes/handlers/get_vehicle_variants.php?model=${encodeURIComponent(selectedModel)}`)
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            variantSelect.innerHTML = '<option value="">Select variant</option>';
            
            if (data.variants && data.variants.length > 0) {
              data.variants.forEach(variant => {
                const option = document.createElement('option');
                option.value = variant.variant;
                option.textContent = variant.variant;
                option.dataset.vehicleId = variant.id; // Store vehicle ID
                variantSelect.appendChild(option);
              });
            } else {
              variantSelect.innerHTML = '<option value="">No variants available</option>';
            }
          } else {
            variantSelect.innerHTML = '<option value="">Error loading variants</option>';
          }
          variantSelect.disabled = false;
        })
        .catch(error => {
          console.error('Error:', error);
          variantSelect.innerHTML = '<option value="">Error loading variants</option>';
          variantSelect.disabled = false;
        });
    });
    
    // Update vehicle_id when variant is selected
    variantSelect.addEventListener('change', function() {
      const selectedOption = this.options[this.selectedIndex];
      const vehicleIdInput = document.querySelector('input[name="vehicle_id"]');
      
      if (vehicleIdInput && selectedOption && selectedOption.dataset && selectedOption.dataset.vehicleId) {
        vehicleIdInput.value = selectedOption.dataset.vehicleId;
      }
    });
  }
});
