<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { 
            font-family: 'Helvetica', sans-serif; 
            text-align: center; 
            border: 15px solid #2c3e50; 
            padding: 40px; 
            color: #333; 
            height: 90%;
        }
        .container {
            border: 2px solid #95a5a6;
            padding: 30px;
            height: 95%;
        }
        .header { 
            font-size: 40px; 
            font-weight: bold; 
            color: #2c3e50; 
            margin-bottom: 10px; 
            text-transform: uppercase; 
            letter-spacing: 2px;
        }
        .sub-header { 
            font-size: 18px; 
            color: #7f8c8d; 
            margin-bottom: 30px; 
            text-transform: uppercase; 
        }
        
        .name { 
            font-size: 45px; 
            font-weight: bold; 
            color: #2c3e50;
            border-bottom: 2px solid #ccc; 
            display: inline-block; 
            padding: 0 40px 10px 40px;
            margin: 20px 0;
            font-style: italic;
        }
        
        .text-block { 
            font-size: 18px; 
            margin: 20px 0; 
            color: #555; 
        }
        
        .course { 
            font-size: 32px; 
            font-weight: bold; 
            color: #27ae60; 
            margin: 10px 0 40px 0; 
        }
        
        /* Grade Box Styling */
        .grade-box {
            border: 3px double #e67e22;
            background-color: #fdf2e9;
            padding: 20px 40px;
            display: inline-block;
            margin: 20px auto;
            border-radius: 8px;
        }
        .grade-label { 
            font-size: 14px; 
            text-transform: uppercase; 
            color: #d35400; 
            font-weight: bold; 
            letter-spacing: 1px;
        }
        .grade-value { 
            font-size: 50px; 
            font-weight: 900; 
            color: #d35400; 
            line-height: 1; 
            margin: 10px 0; 
        }
        .score-value { 
            font-size: 20px; 
            font-weight: bold; 
            color: #2c3e50; 
        }

        .footer { 
            margin-top: 60px; 
            font-size: 12px; 
            color: #95a5a6; 
            border-top: 1px solid #eee; 
            padding-top: 20px; 
        }
        
        .seal {
            font-size: 20px;
            color: #c0392b;
            margin-top: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Certificate of Completion</div>
        
        <div class="sub-header">This is to certify that</div>
        
        <div class="name">{{ $student_name }}</div>
        
        <div class="text-block">Has successfully completed the course requirements and passed all assessments for</div>
        
        <div class="course">{{ $course_title }}</div>

        <div class="grade-box">
            <div class="grade-label">Final Grade</div>
            <div class="grade-value">{{ $grade }}</div>
            <div class="score-value">{{ $percentage }}%</div>
        </div>
        
        <div class="footer">
            <p>Issued on: {{ $date }}</p>
            <p>Certificate ID: {{ $certificate_id }}</p>
            <div class="seal">VERIFIED</div>
            <div><p>This is a computer generated certificate and does not need any signature .</p></div>
        </div>
    </div>
</body>
</html>