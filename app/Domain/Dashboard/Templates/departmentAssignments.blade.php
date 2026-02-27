@extends($layout)

@section('content')
<x-global::pageheader :icon="'fa fa-sitemap'">
    <h5>{{ __('label.overview') }}</h5>
    <h1>Department Assignments</h1>
</x-global::pageheader>

<div class="maincontent">
    {!! $tpl->displayNotification() !!}

    <div class="maincontentinner">
        @foreach (($departmentAssignments ?? []) as $departmentBlock)
            <h4 class="widgettitle title-light">{{ $departmentBlock['department'] }}</h4>
            @if (count($departmentBlock['rows']) === 0)
                <p>No active users found for this department.</p>
            @else
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Open Tasks</th>
                            <th>Sample Assignments</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($departmentBlock['rows'] as $row)
                            <tr>
                                <td>{{ $row['user']['firstname'] ?: $row['user']['username'] }} {{ $row['user']['lastname'] }}</td>
                                <td>{{ $row['user']['role'] }}</td>
                                <td>{{ $row['openCount'] }}</td>
                                <td>
                                    @if (count($row['tickets']) === 0)
                                        <span>No open assignments</span>
                                    @else
                                        @foreach ($row['tickets'] as $ticket)
                                            <div>
                                                <a href="{{ BASE_URL }}/tickets/showTicket/{{ $ticket['id'] }}">
                                                    #{{ $ticket['id'] }} {{ $ticket['headline'] ?? 'Ticket' }}
                                                </a>
                                                <small>({{ $ticket['projectName'] ?? '-' }})</small>
                                            </div>
                                        @endforeach
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            <br />
        @endforeach
    </div>
</div>
@endsection
