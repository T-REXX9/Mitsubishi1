<?php
session_start();
include_once(dirname(__DIR__) . '/includes/database/db_conn.php');
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Customer') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Offers - Mitsubishi Motors</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 25%, #2d1b1b 50%, #8b0000 75%, #b80000 100%);
            min-height: 100vh;
            color: white;
        }

        .header {
            background: rgba(0, 0, 0, 0.4);
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            width: 50px;
            height: auto;
        }

        .brand-text {
            font-size: 1.2rem;
            font-weight: 700;
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-btn {
            background: rgba(255, 215, 0, 0.1);
            color: #ffd700;
            border: 1px solid rgba(255, 215, 0, 0.3);
            padding: 10px 20px;
            border-radius: 15px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .nav-btn:hover {
            background: rgba(255, 215, 0, 0.2);
            transform: translateY(-2px);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 30px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .page-header h1 {
            font-size: 3rem;
            background: linear-gradient(45deg, #ffd700, #ffed4e, #fff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
        }

        .offers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .offer-card {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 215, 0, 0.1);
            position: relative;
        }

        .offer-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .offer-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(45deg, #ff6b35, #f7931e);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            z-index: 5;
        }

        .offer-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(45deg, #333, #555);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: #ffd700;
            position: relative;
        }

        .offer-content {
            padding: 30px;
        }

        .offer-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffd700;
            margin-bottom: 15px;
        }

        .offer-description {
            margin-bottom: 20px;
            opacity: 0.9;
            line-height: 1.6;
        }

        .offer-details {
            margin-bottom: 25px;
        }

        .offer-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .offer-detail span:first-child {
            color: #ffd700;
            font-weight: 600;
        }

        .offer-expires {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid rgba(255, 0, 0, 0.3);
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
            color: #ff6b6b;
            font-size: 0.9rem;
        }

        .offer-btn {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            color: #1a1a1a;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
        }

        .offer-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
        }

        .financing-section {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 40px;
            border: 1px solid rgba(255, 215, 0, 0.1);
            margin-top: 50px;
        }

        .financing-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .financing-header h2 {
            color: #ffd700;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .financing-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .financing-option {
            background: rgba(255, 255, 255, 0.05);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            border: 1px solid rgba(255, 215, 0, 0.2);
        }

        .financing-rate {
            font-size: 2.5rem;
            font-weight: 800;
            color: #ffd700;
            margin-bottom: 10px;
        }

        .financing-term {
            font-size: 1.1rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }

        .financing-details {
            font-size: 0.9rem;
            opacity: 0.8;
            line-height: 1.5;
        }

        @media (max-width: 575px) {
            .header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            .container {
                padding: 30px 20px;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .offers-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .financing-section {
                padding: 25px;
            }

            .financing-options {
                grid-template-columns: 1fr;
            }
        }

        @media (min-width: 576px) and (max-width: 767px) {
            .offers-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (min-width: 768px) and (max-width: 991px) {
            .offers-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo-section">
            <img src="../../includes/images/Mitsubishi_logo.png" alt="Mitsubishi Logo" class="logo">
            <div class="brand-text">MITSUBISHI MOTORS</div>
        </div>
        <div class="nav-links">
            <a href="customer.php" class="nav-btn"><i class="fas fa-home"></i> Dashboard</a>
            <a href="logout.php" class="nav-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h1>Special Offers</h1>
            <p>Exclusive deals and promotions for Mitsubishi customers</p>
        </div>

        <div class="offers-grid">
            <div class="offer-card">
                <div class="offer-badge">Limited Time</div>
                <div class="offer-image">
                    <i class="fas fa-car"></i>
                </div>
                <div class="offer-content">
                    <div class="offer-title">New Outlander Lease Special</div>
                    <div class="offer-description">
                        Lease a brand new 2024 Mitsubishi Outlander and enjoy low monthly payments with excellent warranty coverage.
                    </div>
                    <div class="offer-details">
                        <div class="offer-detail">
                            <span>Monthly Payment:</span>
                            <span>$299/month</span>
                        </div>
                        <div class="offer-detail">
                            <span>Down Payment:</span>
                            <span>$2,999</span>
                        </div>
                        <div class="offer-detail">
                            <span>Lease Term:</span>
                            <span>36 months</span>
                        </div>
                        <div class="offer-detail">
                            <span>Mileage:</span>
                            <span>12,000/year</span>
                        </div>
                    </div>
                    <div class="offer-expires">
                        <i class="fas fa-clock"></i> Offer expires December 31, 2024
                    </div>
                    <button class="offer-btn">
                        <i class="fas fa-tag"></i> Claim Offer
                    </button>
                </div>
            </div>

            <div class="offer-card">
                <div class="offer-badge">Hot Deal</div>
                <div class="offer-image">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="offer-content">
                    <div class="offer-title">Outlander PHEV Cash Back</div>
                    <div class="offer-description">
                        Purchase a new Outlander PHEV and receive substantial cash back plus federal tax incentives.
                    </div>
                    <div class="offer-details">
                        <div class="offer-detail">
                            <span>Cash Back:</span>
                            <span>$3,000</span>
                        </div>
                        <div class="offer-detail">
                            <span>Federal Tax Credit:</span>
                            <span>Up to $7,500</span>
                        </div>
                        <div class="offer-detail">
                            <span>Total Savings:</span>
                            <span>Up to $10,500</span>
                        </div>
                        <div class="offer-detail">
                            <span>Financing:</span>
                            <span>1.9% APR available</span>
                        </div>
                    </div>
                    <div class="offer-expires">
                        <i class="fas fa-clock"></i> Offer expires January 15, 2025
                    </div>
                    <button class="offer-btn">
                        <i class="fas fa-tag"></i> Claim Offer
                    </button>
                </div>
            </div>

            <div class="offer-card">
                <div class="offer-badge">Service Special</div>
                <div class="offer-image">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="offer-content">
                    <div class="offer-title">Free Oil Change Package</div>
                    <div class="offer-description">
                        Purchase any new Mitsubishi vehicle and receive a complimentary oil change package for the first year.
                    </div>
                    <div class="offer-details">
                        <div class="offer-detail">
                            <span>Oil Changes:</span>
                            <span>Up to 4 free</span>
                        </div>
                        <div class="offer-detail">
                            <span>Includes:</span>
                            <span>Filter & inspection</span>
                        </div>
                        <div class="offer-detail">
                            <span>Value:</span>
                            <span>$240 savings</span>
                        </div>
                        <div class="offer-detail">
                            <span>Valid for:</span>
                            <span>12 months</span>
                        </div>
                    </div>
                    <div class="offer-expires">
                        <i class="fas fa-clock"></i> Offer expires March 31, 2025
                    </div>
                    <button class="offer-btn">
                        <i class="fas fa-tag"></i> Claim Offer
                    </button>
                </div>
            </div>

            <div class="offer-card">
                <div class="offer-badge">College Grad</div>
                <div class="offer-image">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="offer-content">
                    <div class="offer-title">College Graduate Program</div>
                    <div class="offer-description">
                        Recent college graduates can enjoy special financing rates and cash incentives on select Mitsubishi vehicles.
                    </div>
                    <div class="offer-details">
                        <div class="offer-detail">
                            <span>Cash Bonus:</span>
                            <span>$500</span>
                        </div>
                        <div class="offer-detail">
                            <span>APR Reduction:</span>
                            <span>0.5% off</span>
                        </div>
                        <div class="offer-detail">
                            <span>No Payment:</span>
                            <span>First 90 days</span>
                        </div>
                        <div class="offer-detail">
                            <span>Eligibility:</span>
                            <span>Grad within 2 years</span>
                        </div>
                    </div>
                    <div class="offer-expires">
                        <i class="fas fa-clock"></i> Ongoing program
                    </div>
                    <button class="offer-btn">
                        <i class="fas fa-tag"></i> Learn More
                    </button>
                </div>
            </div>

            <div class="offer-card">
                <div class="offer-badge">Trade-In</div>
                <div class="offer-image">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="offer-content">
                    <div class="offer-title">Trade-In Value Boost</div>
                    <div class="offer-description">
                        Get an additional bonus on your trade-in value when you purchase a new Mitsubishi vehicle.
                    </div>
                    <div class="offer-details">
                        <div class="offer-detail">
                            <span>Bonus Value:</span>
                            <span>Up to $2,000</span>
                        </div>
                        <div class="offer-detail">
                            <span>Any Condition:</span>
                            <span>Running or not</span>
                        </div>
                        <div class="offer-detail">
                            <span>Free Appraisal:</span>
                            <span>No obligation</span>
                        </div>
                        <div class="offer-detail">
                            <span>Instant Quote:</span>
                            <span>Online available</span>
                        </div>
                    </div>
                    <div class="offer-expires">
                        <i class="fas fa-clock"></i> Offer expires February 28, 2025
                    </div>
                    <button class="offer-btn">
                        <i class="fas fa-tag"></i> Get Quote
                    </button>
                </div>
            </div>

            <div class="offer-card">
                <div class="offer-badge">Military</div>
                <div class="offer-image">
                    <i class="fas fa-flag-usa"></i>
                </div>
                <div class="offer-content">
                    <div class="offer-title">Military Appreciation</div>
                    <div class="offer-description">
                        Active duty, veterans, and military families receive exclusive pricing and financing benefits.
                    </div>
                    <div class="offer-details">
                        <div class="offer-detail">
                            <span>Military Rebate:</span>
                            <span>$750</span>
                        </div>
                        <div class="offer-detail">
                            <span>Special Financing:</span>
                            <span>0% APR available</span>
                        </div>
                        <div class="offer-detail">
                            <span>Family Eligible:</span>
                            <span>Spouses & dependents</span>
                        </div>
                        <div class="offer-detail">
                            <span>Documentation:</span>
                            <span>Military ID required</span>
                        </div>
                    </div>
                    <div class="offer-expires">
                        <i class="fas fa-clock"></i> Year-round program
                    </div>
                    <button class="offer-btn">
                        <i class="fas fa-tag"></i> Thank You
                    </button>
                </div>
            </div>
        </div>

        <div class="financing-section">
            <div class="financing-header">
                <h2>Current Financing Rates</h2>
                <p>Competitive financing options for qualified buyers</p>
            </div>
            <div class="financing-options">
                <div class="financing-option">
                    <div class="financing-rate">1.9%</div>
                    <div class="financing-term">APR for 36 months</div>
                    <div class="financing-details">Available on select new vehicles with approved credit. Offer varies by model and trim level.</div>
                </div>
                <div class="financing-option">
                    <div class="financing-rate">2.9%</div>
                    <div class="financing-term">APR for 60 months</div>
                    <div class="financing-details">Extended financing with competitive rates. Perfect for lower monthly payments.</div>
                </div>
                <div class="financing-option">
                    <div class="financing-rate">3.9%</div>
                    <div class="financing-term">APR for 72 months</div>
                    <div class="financing-details">Longest term available with excellent rates for maximum affordability.</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
