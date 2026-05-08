/**
 * Quiz Banner System
 * Injects and manages the quiz banner ONLY on nutrismart-website.html.
 */

document.addEventListener("DOMContentLoaded", () => {
    // ONLY run on the main landing page
    if (!window.location.pathname.includes('nutrismart-website.html') && window.location.pathname !== '/' && !window.location.pathname.endsWith('NutriSmart/')) {
        return;
    }

    // Only inject if it doesn't already exist in the HTML
    if (document.getElementById('quizBanner')) return;

    const bannerHTML = `
        <div class="quiz-banner" id="quizBanner" style="
            position: fixed !important; 
            bottom: 25px !important; 
            right: 25px !important; 
            left: auto !important;
            width: auto !important; 
            max-width: 320px !important;
            background: #ffffff !important; 
            z-index: 999999 !important; 
            border-radius: 20px !important;
            padding: 1.5rem !important;
            box-shadow: 0 15px 40px rgba(0,0,0,0.15) !important;
            border: 1px solid rgba(0,0,0,0.05) !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 1rem !important;
            align-items: center !important;
            text-align: center !important;
        ">
            <button class="quiz-close" id="closeQuizBanner" style="
                position: absolute !important;
                top: 10px !important;
                right: 10px !important;
                background: rgba(0,0,0,0.05) !important;
                border: none !important;
                width: 28px !important;
                height: 28px !important;
                border-radius: 50% !important;
                cursor: pointer !important;
                display: grid !important;
                place-items: center !important;
            ">
                <svg viewBox="0 0 24 24" width="16" height="16" stroke="#666" stroke-width="2.5" fill="none">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <div class="quiz-banner-text" style="
                font-size: 0.95rem !important;
                line-height: 1.4 !important;
                margin-top: 5px !important;
                padding: 0 10px !important;
            ">
                Découvre ton profil alimentaire gratuitement en 2 minutes
            </div>
            <a href="#quiz" class="quiz-banner-btn" style="
                background: #D18A5B !important;
                color: white !important;
                text-decoration: none !important;
                padding: 0.7rem 1.8rem !important;
                border-radius: 50px !important;
                font-weight: 800 !important;
                font-size: 0.85rem !important;
                text-transform: uppercase !important;
                letter-spacing: 0.05em !important;
                box-shadow: 0 8px 20px rgba(209, 138, 91, 0.3) !important;
                transition: all 0.3s ease !important;
                display: inline-block !important;
                border: none !important;
            ">FAIRE LE QUIZ</a>
        </div>
    `;

    document.body.insertAdjacentHTML('afterbegin', bannerHTML);

    const quizBanner = document.getElementById('quizBanner');
    const closeBtn = document.getElementById('closeQuizBanner');

    // Handle Close Button
    if (closeBtn && quizBanner) {
        closeBtn.addEventListener('click', () => {
            quizBanner.style.setProperty('display', 'none', 'important');
            sessionStorage.setItem('quizBannerClosed', 'true');
        });

        // Don't show if already closed in this session
        if (sessionStorage.getItem('quizBannerClosed') === 'true') {
            quizBanner.style.setProperty('display', 'none', 'important');
        } else {
            quizBanner.style.setProperty('display', 'flex', 'important');
        }
    }
});

