{root:}
	<h1>Session REST-Сервис</h1>

	<ul> 
		<li><a href="/{root}/users">users</a></li>
		<li><a href="/{root}/clear">clear/{email}</a></li>
		<li><a href="/{root}/stat">stat</a></li>
	</ul>
{users:}
	<h1>Все зарегистрированные пользователи</h1>
	<table class="table table-striped">
		<thead><tr>{head::head}</tr></thead>
		<tbody>{table::row}</tbody>
	</table>
	{head:}<th>{.}</th>
	{row:}<tr>{::cell}</tr>
		{cell:}<td>{.}</td>