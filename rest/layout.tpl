{root:}
	<h1>Session REST-Сервис</h1>

	<p> 
		<a href="/{root}/users">users</a>
	</p>
{users:}
	<h1>Все зарегистрированные пользователи</h1>
	<table class="table table-striped">
		<thead><tr>{head::head}</tr></thead>
		<tbody>{table::row}</tbody>
	</table>
	{head:}<th>{.}</th>
	{row:}<tr>{::cell}</tr>
		{cell:}<td>{.}</td>