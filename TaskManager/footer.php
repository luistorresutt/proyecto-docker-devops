</div> <hr />
    <footer class="container text-center text-muted mt-4 mb-4">
        <p>&copy; <?= date('Y') ?> - InnoviTech.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const container = document.getElementById('flash-message-container');
            if (container && container.children.length > 0) {
                setTimeout(() => container.classList.add('fade-out'), 5000);
                setTimeout(() => container.remove(), 6000);
            }
        });
    </script>
</body>
</html>