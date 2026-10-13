<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Report;
use App\Services\PerformanceService;
use DomainException;
final class ReportController extends Controller
{
    public function __construct(private readonly Report $reports, private readonly PerformanceService $performance) {}
    public function index(Request $request, array $user): array
    {
        if ($user['role'] !== 'manager') throw new DomainException('Manager access required.');
        $from = Validator::date($request->query('from',date('Y-m-01')));
        $to = Validator::date($request->query('to',date('Y-m-d')));
        if ($from > $to) throw new DomainException('Start date must precede end date.');
        $reports = $this->performance->evaluate($this->reports->performance($from,$to));
        if ($request->hasQuery('csv')) $this->export($reports);
        return compact('from','to','reports');
    }
    private function export(array $reports): never
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="performance.csv"');
        $out = fopen('php://output','w');
        fputcsv($out,['Employee','Due tasks','Completed','On time','Score'],',','"','');
        foreach ($reports as $row) {
            $name = preg_match('/^[=+@\-\t\r\n]/',$row['name']) ? "'".$row['name'] : $row['name'];
            fputcsv($out,[$name,$row['total'],$row['completed'],$row['on_time'],$row['score']??'N/A'],',','"','');
        }
        fclose($out); exit;
    }
}
