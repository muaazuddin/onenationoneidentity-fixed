document.addEventListener('DOMContentLoaded', function() {
    let warningInterval;
    let logoutTimer;
    let lastActivity = Date.now();
    
    const SESSION_TIMEOUT = 300000; // 5 minutes (300 seconds)
    const WARNING_INTERVAL = 15000; // 15 seconds - exactly what you want
    const WARNING_DISPLAY_TIME = 5000; // Show for 5 seconds

    console.log('Session timeout system initialized');
    console.log('Session timeout:', SESSION_TIMEOUT / 1000 + ' seconds');
    console.log('Warning interval:', WARNING_INTERVAL / 1000 + ' seconds');

    function startTimers() {
        console.log('Starting session timeout warnings');
        clearTimers();
        
        // Show first warning after 15 seconds
        setTimeout(showTimeoutWarning, WARNING_INTERVAL);
        
        // Periodic warnings every 15 seconds
        warningInterval = setInterval(showTimeoutWarning, WARNING_INTERVAL);
        
        // Logout timer after 5 minutes
        logoutTimer = setTimeout(logoutUser, SESSION_TIMEOUT);
        
        console.log('Timers started successfully');
    }

    function showTimeoutWarning() {
        const timeElapsed = Date.now() - lastActivity;
        const timeRemaining = SESSION_TIMEOUT - timeElapsed;
        
        if (timeRemaining <= 0) {
            return; // Don't show warning if time is up
        }
        
        const minutesRemaining = Math.floor(timeRemaining / 60000);
        const secondsRemaining = Math.floor((timeRemaining % 60000) / 1000);
        
        const timeDisplay = minutesRemaining > 0 ? 
            `${minutesRemaining} minutes ${secondsRemaining} seconds` : 
            `${secondsRemaining} seconds`;
        
        console.log('Showing timeout warning:', timeDisplay, 'remaining');
        
        // Remove any existing notifications first
        removeExistingNotifications();
        
        const notification = document.createElement('div');
        notification.className = 'timeout-notification';
        
        // Determine the theme based on time remaining
        let theme = 'normal';
        let icon = 'fa-clock';
        
        if (timeRemaining <= 60000) { // Less than 1 minute
            theme = 'critical';
            icon = 'fa-exclamation-triangle';
        } else if (timeRemaining <= 120000) { // Less than 2 minutes
            theme = 'urgent';
            icon = 'fa-hourglass-half';
        } else if (timeRemaining <= 180000) { // Less than 3 minutes
            theme = 'warning';
            icon = 'fa-clock';
        }
        
        notification.classList.add(theme);
        
        notification.innerHTML = `
            <div class="notification-content">
                <div class="notification-icon">
                    <i class="fas ${icon}"></i>
                </div>
                <div class="notification-text">
                    <div class="notification-title">Session Timeout Warning</div>
                    <div class="notification-time">Time remaining: <strong>${timeDisplay}</strong></div>
                    <div class="notification-hint">Any activity will extend your session</div>
                </div>
                <button class="btn-close btn-close-white" onclick="this.closest('.timeout-notification').remove()"></button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove notification after display time with smooth animation
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'slideOutUp 0.4s ease forwards';
                setTimeout(() => {
                    if (notification.parentNode) notification.remove();
                }, 400);
            }
        }, WARNING_DISPLAY_TIME);
    }

    function updateActivity() {
        lastActivity = Date.now();
        console.log('Activity detected - session extended');
    }

    function resetTimers() {
        updateActivity();
        console.log('Resetting session timers');
        clearTimers();
        startTimers();
        
        // Show activity confirmation
        showActivityConfirmation();
    }

    function showActivityConfirmation() {
        const existingConfirmation = document.querySelector('.timeout-notification.success');
        if (existingConfirmation) {
            existingConfirmation.remove();
        }
        
        const confirmation = document.createElement('div');
        confirmation.className = 'timeout-notification success';
        confirmation.innerHTML = `
            <div class="notification-content">
                <div class="notification-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="notification-text">
                    <div class="notification-title">Session Extended</div>
                    <div class="notification-time">Activity detected</div>
                    <div class="notification-hint">Session timer has been reset</div>
                </div>
                <button class="btn-close btn-close-white" onclick="this.closest('.timeout-notification').remove()"></button>
            </div>
        `;
        
        document.body.appendChild(confirmation);
        
        // Auto-remove after 2 seconds
        setTimeout(() => {
            if (confirmation.parentNode) {
                confirmation.style.animation = 'slideOutUp 0.4s ease forwards';
                setTimeout(() => {
                    if (confirmation.parentNode) confirmation.remove();
                }, 400);
            }
        }, 2000);
    }

    function clearTimers() {
        if (warningInterval) {
            clearInterval(warningInterval);
            warningInterval = null;
        }
        if (logoutTimer) {
            clearTimeout(logoutTimer);
            logoutTimer = null;
        }
    }

    function removeExistingNotifications() {
        const existingNotifications = document.querySelectorAll('.timeout-notification');
        existingNotifications.forEach(notification => {
            if (notification.parentNode && !notification.classList.contains('success')) {
                notification.remove();
            }
        });
    }

    function logoutUser() {
        console.log('Logging out user due to inactivity');
        clearTimers();
        
        const notification = document.createElement('div');
        notification.className = 'timeout-notification logout';
        notification.innerHTML = `
            <div class="notification-content">
                <div class="notification-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <div class="notification-text">
                    <div class="notification-title">Session Expired</div>
                    <div class="notification-time">Due to inactivity</div>
                    <div class="notification-hint">Redirecting to login page...</div>
                </div>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            window.location.href = 'index.php?timeout=1';
        }, 2000);
    }

    // Start the timers immediately when page loads
    startTimers();
    
    // User activity detection - VERY LIGHTWEIGHT
    const activityEvents = [
        'mousemove', 'mousedown', 'click', 'scroll', 
        'keypress', 'keydown', 'input', 'touchstart',
        'focus', 'blur', 'resize'
    ];
    
    let activityTimeout;
    activityEvents.forEach(event => {
        document.addEventListener(event, function() {
            // Debounce activity detection to avoid too many resets
            clearTimeout(activityTimeout);
            activityTimeout = setTimeout(() => {
                resetTimers();
            }, 500);
        }, { passive: true });
    });

    console.log('Activity listeners attached - no interference with mouse clicks');

    // Add a beautiful status indicator
    const statusIndicator = document.createElement('div');
    statusIndicator.id = 'sessionStatusIndicator';
    statusIndicator.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: linear-gradient(135deg, rgba(33, 150, 243, 0.9), rgba(25, 118, 210, 0.9));
        color: white;
        padding: 8px 16px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        z-index: 9998;
        pointer-events: none;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s ease;
    `;
    statusIndicator.innerHTML = '🟢 Session Active';
    document.body.appendChild(statusIndicator);

    // Update status every second
    setInterval(() => {
        const timeRemaining = SESSION_TIMEOUT - (Date.now() - lastActivity);
        const minutes = Math.floor(timeRemaining / 60000);
        const seconds = Math.floor((timeRemaining % 60000) / 1000);
        
        let statusText = `🟢 ${minutes}m ${seconds}s`;
        let statusBackground = 'linear-gradient(135deg, rgba(33, 150, 243, 0.9), rgba(25, 118, 210, 0.9))';
        
        if (timeRemaining < 60000) {
            statusText = `🔴 ${minutes}m ${seconds}s`;
            statusBackground = 'linear-gradient(135deg, rgba(244, 67, 54, 0.9), rgba(211, 47, 47, 0.9))';
        } else if (timeRemaining < 120000) {
            statusText = `🟠 ${minutes}m ${seconds}s`;
            statusBackground = 'linear-gradient(135deg, rgba(255, 152, 0, 0.9), rgba(245, 124, 0, 0.9))';
        }
        
        statusIndicator.innerHTML = statusText;
        statusIndicator.style.background = statusBackground;
    }, 1000);

});

// Alert system functionality
function loadAlerts() {
    fetch("fetch_alerts.php")
        .then(res => res.json())
        .then(data => {
            const alertList = document.getElementById("alertList");
            const alertCount = document.getElementById("alertCount");

            alertList.innerHTML = "";
            
            if (data.length === 0) {
                alertList.innerHTML = "<li><span class='dropdown-item-text text-muted'>No new alerts</span></li>";
                alertCount.style.display = "none";
                return;
            }

            data.forEach(alert => {
                let icon = "fas fa-info-circle text-primary";
                if (alert.type === "warning") icon = "fas fa-exclamation-triangle text-warning";
                if (alert.type === "danger") icon = "fas fa-exclamation-octagon text-danger";

                alertList.innerHTML += `
                    <li>
                        <span class="dropdown-item">
                            <i class="${icon} me-2"></i>
                            ${alert.message} <br>
                            <small class="text-muted">${alert.created_at}</small>
                        </span>
                    </li>
                `;
            });

            alertCount.innerText = data.length;
            alertCount.style.display = "inline-block";
        })
        .catch(err => console.error('Error loading alerts:', err));
}

// Auto refresh alerts every 10 seconds
setInterval(loadAlerts, 10000);
loadAlerts();

// Simple animation for stats counting
document.addEventListener('DOMContentLoaded', function() {
    const statCards = document.querySelectorAll('.stat-content h2');
    
    statCards.forEach(card => {
        const target = parseInt(card.innerText);
        let current = 0;
        const increment = Math.ceil(target / 50);
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                card.innerText = target;
                clearInterval(timer);
            } else {
                card.innerText = current;
            }
        }, 30);
    });
});