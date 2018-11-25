<script>
	$(document).ready(function () {
        	$(window).keydown(function (event) {
                	if (event.keyCode == 13) {
                        	event.preventDefault();
                                return false;
                        }
                });
        });

</script>
<div style="margin: auto; width: 400px" class="text-center well" id="searchBox">
<h1>Search Images</h1>
<p>
	Enter up to five search terms below.
</p>
<p>
	Search terms can appear in any part of the file name and are not case sensitive.
</p>

<form method="POST" id="searchForm">

	<input type="hidden" name="action" value="search" />

    <div class="well well-sm">

        <input class="form-control" type="text" name="searchTerms[]" placeholder="Search Term..." />

        <input class="form-control" type="text" name="searchTerms[]" placeholder="Search Term..." />
        <input class="form-control" type="text" name="searchTerms[]" placeholder="Search Term..." />
        <input class="form-control" type="text" name="searchTerms[]" placeholder="Search Term..." />
        <input class="form-control" type="text" name="searchTerms[]" placeholder="Search Term..." />
    </div>

    <p>
        <button class="btn btn-primary btn-lg">Search</button>
    </p>
</form>
</div>
<style>
	#searchForm input {
		display: block;
	}
</style>
