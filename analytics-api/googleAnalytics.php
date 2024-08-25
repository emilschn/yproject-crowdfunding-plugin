<?php
require 'vendor/autoload.php';


use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\OrderBy\DimensionOrderBy;
use Google\Analytics\Data\V1beta\OrderBy\MetricOrderBy;
use Google\Analytics\Data\V1beta\Filter;
use Google\Analytics\Data\V1beta\FilterExpression;

putenv('GOOGLE_APPLICATION_CREDENTIALS=./wedogood-cab272061959.json');

class googleAnalytics
{
    private $start_date;
    private $end_date;
    private $path;
    private $propertyId = '395535620';
    public function __construct($start_date, $end_date, $path)
    {
        $this->end_date = $end_date;
        $this->start_date = $start_date;

        $datetime_start_test = new DateTime($start_date);
        $datetime_end_test = new DateTime($end_date);
        if ($datetime_start_test > $datetime_end_test) {
            $this->start_date = $this->end_date;
        }

        $this->path = $path;
    }

    public function get_visits()
    {
        $client = new BetaAnalyticsDataClient();

        $dateRanges = [
            new DateRange([
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
            ]),
        ];

        $dimensions = [
            new Dimension([
                'name' => 'date',
            ]),
            new Dimension([
                'name' => 'pagePath',
            ]),
        ];

        $metrics = [
            new Metric([
                'name' => 'screenPageViews'
            ]),
        ];
        $orderBys = [
            new OrderBy([
                'dimension' => new DimensionOrderBy([
                    'dimension_name' => 'date',
                ]),
            ]),
            new OrderBy([
                'metric' => new MetricOrderBy([
                    'metric_name' => 'screenPageViews',
                ]),
            ]),
        ];

        $response = $client->runReport([
            'property' => 'properties/' . $this->propertyId,
            'dateRanges' => $dateRanges,
            'dimensions' => $dimensions,
            'metrics' => $metrics,
            'orderBys' => $orderBys,
            'dimensionFilter' => new FilterExpression([
                'filter' => new Filter([
                    'field_name' => 'pagePath',
                    'string_filter' => new Filter\StringFilter([
                        'match_type' => Filter\StringFilter\MatchType::BEGINS_WITH,
                        'value' => '/neoney',
                    ])
                ])
            ])
        ]);
        $i = 0;
        echo '[';
        foreach ($response->getRows() as $row) {
            $dimensions = $row->getDimensionValues();
            $date = $dimensions[0]->getValue();
            $pagePath = $dimensions[1]->getValue();
            $metrics = $row->getMetricValues();
            $screenPageViews = $metrics[0]->getValue();
            if ($i == 0) {
                echo '{"date":' . $date . ',"visits":' . $screenPageViews . '}';
            } else {
                echo ',{"date":' . $date . ',"visits":' . $screenPageViews . '}';
            }
        $i++;

        }
        echo ']';

    }

    public function get_sources()
    {
        $client = new BetaAnalyticsDataClient();
        $dateRanges = [
            new DateRange([
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
            ]),
        ];
        $dimensions = [
            new Dimension([
                'name' => 'sessionDefaultChannelGrouping',
            ]),
        ];
        $metrics = [
            new Metric([
                'name' => 'sessions'
            ]),
        ];

        $orderBys = [
            new OrderBy([
                'metric' => new MetricOrderBy([
                    'metric_name' => 'sessions',
                ]),
            ]),
        ];

        $response = $client->runReport([
            'property' => 'properties/' . $this->propertyId,
            'dateRanges' => $dateRanges,
            'dimensions' => $dimensions,
            'metrics' => $metrics,
            'orderBys' => $orderBys,
            'dimensionFilter' => new FilterExpression([
                'filter' => new Filter([
                    'field_name' => 'pagePath',
                    'string_filter' => new Filter\StringFilter([
                        'match_type' => Filter\StringFilter\MatchType::BEGINS_WITH,
                        'value' => '/neoney',
                    ])
                ])
            ])

        ]);
        $i = 0;
        echo '[';
        foreach ($response->getRows() as $row) {
            $dimensions = $row->getDimensionValues();
            $channelGrouping = $dimensions[0]->getValue();
            $metrics = $row->getMetricValues();
            $sessions = $metrics[0]->getValue();
            $sourceFr = str_replace("Organic Social", "Réseaux sociaux", $channelGrouping); // traduction des termes EN de GA
            $sourceFr = str_replace("Organic Search", "Référencement naturel", $sourceFr); // traduction des termes EN de GA
            $sourceFr = str_replace("Referral", "Lien hypertexte", $sourceFr);
            $sourceFr = str_replace("(Other)", "Autre", $sourceFr);
            $sourceFr = str_replace("Unassigned", "Autre", $sourceFr);
            if ($i == 0) {
                echo '{"source":"' . $sourceFr . '","visits":' . $sessions . '}';
            } else {
                echo ',{"source":"' . $sourceFr . '","visits":' . $sessions . '}';
            }
            $i++;
        }
        echo ']';
    }

    public function get_cities()
    {
        $client = new BetaAnalyticsDataClient();
        $dateRanges = [
            new DateRange([
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
            ]),
        ];

        $dimensions = [
            new Dimension([
                'name' => 'city',
            ]),
        ];

        $metrics = [
            new Metric([
                'name' => 'sessions'
            ]),
        ];

        $orderBys = [
            new OrderBy([
                'metric' => new MetricOrderBy([
                    'metric_name' => 'sessions',
                ]),
            ]),
        ];
        $response = $client->runReport([
            'property' => 'properties/' . $this->propertyId,
            'dateRanges' => $dateRanges,
            'dimensions' => $dimensions,
            'metrics' => $metrics,
            'orderBys' => $orderBys,
            'dimensionFilter' => new FilterExpression([
                'filter' => new Filter([
                    'field_name' => 'pagePath',
                    'string_filter' => new Filter\StringFilter([
                        'match_type' => Filter\StringFilter\MatchType::BEGINS_WITH,
                        'value' => '/neoney',
                    ])
                ])
            ])

        ]);
        $i = 0;
        
        echo '[';
        foreach ($response->getRows() as $row) {
            $dimensions = $row->getDimensionValues();
            $city = $dimensions[0]->getValue();
            $metrics = $row->getMetricValues();
            $sessions = $metrics[0]->getValue();
            if ($i == 0) {
                echo '{"cities":"' . $city . '","visits":' . $sessions . '}';
            } else {
                echo ',{"cities":"' . $city . '","visits":' . $sessions . '}';
            }
            $i++;

        }
        echo ']';
    }
}
