            </div> <!-- End content-area -->
        </main>
    </div> <!-- End dashboard-wrapper -->
    <script>
        function toggleSidebar(){
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('open');
        }
        // Auto-hide flash alerts after 5s
        setTimeout(function(){
            var el=document.getElementById('flash-alert');
            if(el) el.style.display='none';
        },5000);
    </script>
</body>
</html>
