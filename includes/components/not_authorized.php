<?php
// Reusable 403 / Not Authorized UI component
// Assumes common-styles.css is already included by the parent layout
?>
<div class="not-authorized" role="region" aria-labelledby="na-title" aria-describedby="na-desc">
  <div class="na-icon" aria-hidden="true">
    <i class="fas fa-ban"></i>
  </div>
  <h1 id="na-title">Not Authorized</h1>
  <p id="na-desc">You do not have permission to access this page. If you believe this is an error, contact your administrator.</p>
  <div class="na-actions">
    <button class="btn-secondary" type="button" onclick="if(history.length > 1){history.back();}else{window.location.href='dashboard.php';}" aria-label="Go back to previous page">Go back</button>
    <a class="btn-primary" href="dashboard.php" aria-label="Go to dashboard">Go to Dashboard</a>
  </div>
</div>

<style>
  .not-authorized {
    background: var(--primary-light);
    border: 1px solid var(--border-light);
    border-radius: 12px;
    padding: 30px;
    box-shadow: var(--shadow-light);
    display: flex;
    flex-direction: column;
    gap: 14px;
    align-items: flex-start;
    max-width: 720px;
    margin: 40px auto;
  }

  .na-icon {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: var(--primary-red);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: var(--shadow-light);
  }

  .na-icon i {
    font-size: 22px;
  }

  .not-authorized h1 {
    font-size: 22px;
    color: var(--text-dark);
    margin: 0;
  }

  .not-authorized p {
    font-size: 14px;
    color: var(--text-light);
    line-height: 1.6;
    margin: 0 0 6px 0;
  }

  .na-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }

  .na-actions .btn-primary,
  .na-actions .btn-secondary {
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .not-authorized :is(button, a):focus {
    outline: 3px solid var(--accent-blue);
    outline-offset: 2px;
  }
</style>