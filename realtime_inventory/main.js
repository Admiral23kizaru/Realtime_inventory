// Fade-in and slide-in animations on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.fade-in').forEach(function(el) {
        el.classList.add('fade-in');
    });
    document.querySelectorAll('.slide-in').forEach(function(el) {
        el.classList.add('slide-in');
    });
});

// Toast notification function
function showToast(message, type = 'success') {
    let toast = document.createElement('div');
    toast.className = 'toast ' + (type === 'success' ? 'bg-success text-white' : 'bg-danger text-white');
    toast.innerText = message;
    document.body.appendChild(toast);
    $(toast).fadeIn(300);
    setTimeout(function() {
        $(toast).fadeOut(400, function() { toast.remove(); });
    }, 2200);
}

// Animate table row on add
function animateRow(row) {
    row.style.backgroundColor = '#dbeafe';
    setTimeout(function() {
        row.style.transition = 'background 0.7s';
        row.style.backgroundColor = '';
    }, 700);
} 