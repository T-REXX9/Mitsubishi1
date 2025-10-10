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
    <title>Support & Contact - Mitsubishi Motors</title>
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
            max-width: 1200px;
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

        .support-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .support-card {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255, 215, 0, 0.1);
            transition: all 0.3s ease;
            text-align: center;
        }

        .support-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }

        .support-icon {
            font-size: 3rem;
            color: #ffd700;
            margin-bottom: 20px;
        }

        .support-card h3 {
            color: #ffd700;
            font-size: 1.4rem;
            margin-bottom: 15px;
        }

        .support-card p {
            margin-bottom: 20px;
            opacity: 0.9;
            line-height: 1.6;
        }

        .contact-info {
            font-size: 1.1rem;
            font-weight: 600;
            color: #ffed4e;
        }

        .contact-form {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 40px;
            border: 1px solid rgba(255, 215, 0, 0.1);
            margin-top: 40px;
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header h2 {
            color: #ffd700;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #ffd700;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #ffd700;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
        }

        .btn-submit {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            color: #1a1a1a;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
        }

        .faq-section {
            margin-top: 50px;
        }

        .faq-title {
            text-align: center;
            color: #ffd700;
            font-size: 2rem;
            margin-bottom: 30px;
        }

        .faq-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            margin-bottom: 15px;
            overflow: hidden;
        }

        .faq-question {
            padding: 20px;
            cursor: pointer;
            border-bottom: 1px solid rgba(255, 215, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .faq-question:hover {
            background: rgba(255, 215, 0, 0.1);
        }

        .faq-answer {
            padding: 0 20px;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
            opacity: 0;
        }

        .faq-answer.active {
            padding: 20px;
            max-height: 200px;
            opacity: 1;
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

            .support-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .contact-form {
                padding: 25px;
            }

            .form-grid {
                grid-template-columns: 1fr;
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
            <h1>Support & Contact</h1>
            <p>We're here to help you with all your Mitsubishi needs</p>
        </div>

        <div class="support-grid">
            <div class="support-card">
                <div class="support-icon">
                    <i class="fas fa-phone"></i>
                </div>
                <h3>Phone Support</h3>
                <p>Talk to our customer service representatives for immediate assistance with your vehicle or account.</p>
                <div class="contact-info">1-800-MITSUBISHI<br>(1-800-648-7824)</div>
            </div>

            <div class="support-card">
                <div class="support-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <h3>Email Support</h3>
                <p>Send us your questions and we'll respond within 24 hours during business days.</p>
                <div class="contact-info">support@mitsubishi.com</div>
            </div>

            <div class="support-card">
                <div class="support-icon">
                    <i class="fas fa-comment"></i>
                </div>
                <h3>Live Chat</h3>
                <p>Chat with our support team in real-time for quick answers to your questions.</p>
                <div class="contact-info">Available 9 AM - 6 PM EST</div>
            </div>

            <div class="support-card">
                <div class="support-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <h3>Service Centers</h3>
                <p>Find your nearest authorized Mitsubishi service center for professional maintenance and repairs.</p>
                <div class="contact-info">Find Location</div>
            </div>

            <div class="support-card">
                <div class="support-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Warranty Support</h3>
                <p>Get information about your vehicle's warranty coverage and claim procedures.</p>
                <div class="contact-info">Warranty Hotline<br>1-800-642-6648</div>
            </div>

            <div class="support-card">
                <div class="support-icon">
                    <i class="fas fa-car-crash"></i>
                </div>
                <h3>Roadside Assistance</h3>
                <p>24/7 emergency roadside assistance for all Mitsubishi vehicles under warranty.</p>
                <div class="contact-info">24/7 Emergency<br>1-888-648-7820</div>
            </div>
        </div>

        <div class="contact-form">
            <div class="form-header">
                <h2>Send Us a Message</h2>
                <p>Fill out the form below and we'll get back to you as soon as possible</p>
            </div>

            <form>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="firstName">First Name</label>
                        <input type="text" id="firstName" name="firstName" required>
                    </div>
                    <div class="form-group">
                        <label for="lastName">Last Name</label>
                        <input type="text" id="lastName" name="lastName" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <select id="subject" name="subject" required>
                            <option value="">Select a topic</option>
                            <option value="vehicle-inquiry">Vehicle Inquiry</option>
                            <option value="service-support">Service Support</option>
                            <option value="warranty">Warranty Question</option>
                            <option value="parts">Parts & Accessories</option>
                            <option value="financing">Financing</option>
                            <option value="complaint">Complaint</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="vin">VIN (if applicable)</label>
                        <input type="text" id="vin" name="vin" placeholder="17-digit VIN">
                    </div>
                </div>
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="6" required placeholder="Please describe your question or concern in detail..."></textarea>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>

        <div class="faq-section">
            <h2 class="faq-title">Frequently Asked Questions</h2>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>What is covered under my Mitsubishi warranty?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Mitsubishi offers a comprehensive warranty including 5-year/60,000-mile new vehicle limited warranty, 10-year/100,000-mile powertrain limited warranty, and 7-year/100,000-mile anti-corrosion/perforation limited warranty.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>How often should I service my Mitsubishi?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>We recommend following the maintenance schedule in your owner's manual. Typically, basic maintenance is required every 7,500 miles or 6 months, whichever comes first.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>Where can I find genuine Mitsubishi parts?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>Genuine Mitsubishi parts are available at all authorized Mitsubishi dealerships and service centers. You can also order parts online through our official parts website.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <span>How do I schedule a service appointment?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>You can schedule a service appointment through your customer dashboard, by calling your local dealership directly, or using our online service scheduling tool.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleFAQ(element) {
            const answer = element.nextElementSibling;
            const icon = element.querySelector('i');
            
            // Close all other FAQ items
            document.querySelectorAll('.faq-answer').forEach(item => {
                if (item !== answer) {
                    item.classList.remove('active');
                }
            });
            
            document.querySelectorAll('.faq-question i').forEach(item => {
                if (item !== icon) {
                    item.className = 'fas fa-chevron-down';
                }
            });
            
            // Toggle current FAQ item
            answer.classList.toggle('active');
            icon.className = answer.classList.contains('active') ? 'fas fa-chevron-up' : 'fas fa-chevron-down';
        }
    </script>
</body>
</html>
