// Sales Agent-specific JavaScript functions
document.addEventListener('DOMContentLoaded', function() {
  // Interface toggle buttons for Sales Agent
  const testDriveBtn = document.getElementById('testDriveBtn');
  const amortizationBtn = document.getElementById('amortizationBtn');
  const inquiryBtn = document.getElementById('inquiryBtn');
  
  const testDriveInterface = document.getElementById('testDriveInterface');
  const amortizationInterface = document.getElementById('amortizationInterface');
  const inquiryInterface = document.getElementById('inquiryInterface');
  
  const closeTestDrive = document.getElementById('closeTestDrive');
  const closeAmortization = document.getElementById('closeAmortization');
  const closeInquiry = document.getElementById('closeInquiry');
  
  // Hide all interfaces
  function hideAllInterfaces() {
    testDriveInterface.style.display = 'none';
    amortizationInterface.style.display = 'none';
    inquiryInterface.style.display = 'none';
  }
  
  // Toggle interfaces
  testDriveBtn.addEventListener('click', function() {
    hideAllInterfaces();
    testDriveInterface.style.display = 'block';
  });
  
  amortizationBtn.addEventListener('click', function() {
    hideAllInterfaces();
    amortizationInterface.style.display = 'block';
  });
  
  inquiryBtn.addEventListener('click', function() {
    hideAllInterfaces();
    inquiryInterface.style.display = 'block';
  });
  
  // Close buttons
  closeTestDrive.addEventListener('click', function() {
    testDriveInterface.style.display = 'none';
  });
  
  closeAmortization.addEventListener('click', function() {
    amortizationInterface.style.display = 'none';
  });
  
  closeInquiry.addEventListener('click', function() {
    inquiryInterface.style.display = 'none';
  });
  
  // Sales Agent specific functions
  window.reviewTestDrive = function(bookingId) {
    alert(`Reviewing test drive request: ${bookingId}`);
  };
  
  window.quickApprove = function(bookingId) {
    if(confirm(`Quick approve test drive request ${bookingId}?`)) {
      alert(`Test drive ${bookingId} approved successfully!`);
    }
  };
});
